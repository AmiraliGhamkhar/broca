<?php

namespace App\Services\Telegram;

use App\Models\AdminActivityLog;
use App\Models\BlogPost;
use App\Models\Course;
use App\Models\Flashcard;
use App\Models\FlashcardDeck;
use App\Models\Invoice;
use App\Models\Note;
use App\Models\Plan;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\Subject;
use App\Models\Subscription;
use App\Models\TelegramAdmin;
use App\Models\TelegramChatSession;
use App\Models\User;
use App\Models\Video;
use App\Services\FreeItemDesignationService;
use App\Support\BrandAssets;
use App\Support\Slug;
use App\Support\StructuredMessageParser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class TelegramBotService
{
    private const SESSION_TTL_MINUTES = 180;

    /** Remote (URL) downloads larger than this are refused — shared-hosting disks are metered. */
    private const MAX_REMOTE_DOWNLOAD_BYTES = 200 * 1024 * 1024;

    private const MAX_REMOTE_DOWNLOAD_MB = 200;

    private const PUBLICATION_TRANSITIONS = [
        'draft' => ['in_review'],
        'in_review' => ['published', 'draft'],
        'published' => ['archived'],
        'archived' => ['draft'],
    ];

    public function __construct(
        private readonly TelegramApiClient $api,
        private readonly FreeItemDesignationService $freeItems,
    ) {
    }

    public function handle(array $update): void
    {
        $callback = $update['callback_query'] ?? null;
        if (is_array($callback)) {
            $this->handleCallbackQuery($callback);

            return;
        }

        $message = $update['message'] ?? $update['edited_message'] ?? null;
        if (! is_array($message)) {
            return;
        }

        $chatId = (int) data_get($message, 'chat.id');
        $telegramUserId = (int) data_get($message, 'from.id');

        if ($chatId === 0 || $telegramUserId === 0) {
            return;
        }

        $from = (array) data_get($message, 'from', []);

        if (! $this->isAuthorizedAdmin($from)) {
            $this->api->sendMessage($chatId, '⛔️ این ربات فقط برای ادمین‌های مجاز سایت فعال است.');

            return;
        }

        $this->rememberAdmin($from);

        $text = trim((string) ($message['text'] ?? ''));

        try {
            if ($text !== '' && $this->handleTextInput($chatId, $telegramUserId, $text)) {
                return;
            }

            $session = $this->currentSession($chatId);
            if (! $session) {
                $this->sendWelcome($chatId);

                return;
            }

            $this->handleWorkflow($session, $message);
        } catch (ValidationException $exception) {
            $this->api->sendMessage($chatId, $this->formatValidationErrors($exception), [
                'reply_markup' => $this->mainMenuMarkup(),
            ]);
        } catch (ModelNotFoundException $exception) {
            // Stale button / wrong id (entity deleted meanwhile): a raw 500
            // would make Telegram retry the update forever.
            $this->api->sendMessage($chatId, '🔍 آیتم موردنظر پیدا نشد؛ احتمالاً حذف شده است. لیست را دوباره باز کنید.', [
                'reply_markup' => $this->mainMenuMarkup(),
            ]);
        } catch (RuntimeException $exception) {
            $this->api->sendMessage($chatId, '⚠️ '.$exception->getMessage(), [
                'reply_markup' => $this->mainMenuMarkup(),
            ]);
        }
    }

    private function handleCallbackQuery(array $callback): void
    {
        $chatId = (int) data_get($callback, 'message.chat.id');
        $telegramUserId = (int) data_get($callback, 'from.id');
        $from = (array) data_get($callback, 'from', []);

        if ($chatId === 0 || $telegramUserId === 0) {
            return;
        }

        $callbackId = (string) ($callback['id'] ?? '');
        if ($callbackId !== '') {
            $this->api->answerCallbackQuery($callbackId);
        }

        if (! $this->isAuthorizedAdmin($from)) {
            $this->api->sendMessage($chatId, '⛔️ این ربات فقط برای ادمین‌های مجاز سایت فعال است.');

            return;
        }

        $this->rememberAdmin($from);

        $data = (string) ($callback['data'] ?? '');

        try {
            $this->dispatchCallbackAction($chatId, $telegramUserId, $data);
        } catch (ValidationException $exception) {
            $this->api->sendMessage($chatId, $this->formatValidationErrors($exception), [
                'reply_markup' => $this->mainMenuMarkup(),
            ]);
        } catch (ModelNotFoundException $exception) {
            // Stale inline button (entity deleted meanwhile): a raw 500 would
            // make Telegram retry the callback update forever.
            $this->api->sendMessage($chatId, '🔍 آیتم موردنظر پیدا نشد؛ احتمالاً حذف شده است. لیست را دوباره باز کنید.', [
                'reply_markup' => $this->mainMenuMarkup(),
            ]);
        } catch (RuntimeException $exception) {
            $this->api->sendMessage($chatId, '⚠️ '.$exception->getMessage(), [
                'reply_markup' => $this->mainMenuMarkup(),
            ]);
        }
    }

    private function dispatchCallbackAction(int $chatId, int $telegramUserId, string $data): void
    {
        $parts = array_pad(explode(':', $data), 5, null);
        [$verb, $a, $b, $c, $d] = $parts;

        if ($verb === 'menu') {
            match ($a) {
                'main' => $this->sendWelcome($chatId),
                'plans' => $this->sendPlansHelp($chatId),
                'blogs' => $this->sendBlogsHelp($chatId),
                'courses' => $this->sendCoursesHelp($chatId),
                'media' => $this->sendMediaHelp($chatId),
                'study' => $this->sendStudyHelp($chatId),
                'users' => $this->sendUsersHelp($chatId),
                'brand' => $this->sendBrandHelp($chatId),
                default => $this->sendWelcome($chatId),
            };

            return;
        }

        if ($verb === 'list') {
            match ($a) {
                'plans' => $this->listPlans($chatId),
                'blogs' => $this->listBlogs($chatId),
                'subjects' => $this->listSubjects($chatId),
                'courses' => $this->listCourses($chatId),
                'videos' => $this->listVideos($chatId),
                'notes' => $this->listNotes($chatId),
                'decks' => $this->listDecks($chatId),
                'quizzes' => $this->listQuizzes($chatId),
                'cards' => $this->listCards($chatId, (int) $b),
                'questions' => $this->listQuestions($chatId, (int) $b),
                'users' => $this->listUsers($chatId, max(1, (int) $b)),
                default => $this->sendWelcome($chatId),
            };

            return;
        }

        if ($verb === 'manage') {
            $this->showManageMenu($chatId, (string) $a, (int) $b);

            return;
        }

        if ($verb === 'new') {
            match ($a) {
                'plan' => $this->startPlanWorkflow($chatId, $telegramUserId, null),
                'blog' => $this->startBlogWorkflow($chatId, $telegramUserId, null),
                'subject' => $this->startSubjectWorkflow($chatId, $telegramUserId, null),
                'course' => $this->startCourseWorkflow($chatId, $telegramUserId, null),
                'video' => $this->startVideoWorkflow($chatId, $telegramUserId, null),
                'note' => $this->startNoteWorkflow($chatId, $telegramUserId, null),
                'deck' => $this->startDeckWorkflow($chatId, $telegramUserId, null),
                'quiz' => $this->startQuizWorkflow($chatId, $telegramUserId, null),
                'card' => $this->startCardWorkflow($chatId, $telegramUserId, ['deck_id' => (int) $b]),
                'question' => $this->startQuestionWorkflow($chatId, $telegramUserId, ['quiz_id' => (int) $b]),
                default => $this->sendWelcome($chatId),
            };

            return;
        }

        if ($verb === 'edit') {
            match ($a) {
                'plan' => $this->startPlanWorkflow($chatId, $telegramUserId, (int) $b),
                'blog' => $this->startBlogWorkflow($chatId, $telegramUserId, (int) $b),
                'subject' => $this->startSubjectWorkflow($chatId, $telegramUserId, (int) $b),
                'course' => $this->startCourseWorkflow($chatId, $telegramUserId, (int) $b),
                'video' => $this->startVideoWorkflow($chatId, $telegramUserId, (int) $b),
                'note' => $this->startNoteWorkflow($chatId, $telegramUserId, (int) $b),
                'deck' => $this->startDeckWorkflow($chatId, $telegramUserId, (int) $b),
                'quiz' => $this->startQuizWorkflow($chatId, $telegramUserId, (int) $b),
                'card' => $this->startCardEditWorkflow($chatId, $telegramUserId, (int) $b),
                'question' => $this->startQuestionEditWorkflow($chatId, $telegramUserId, (int) $b),
                default => $this->sendWelcome($chatId),
            };

            return;
        }

        if ($verb === 'cover') {
            match ($a) {
                'course' => $this->startCoverWorkflow($chatId, $telegramUserId, 'course.cover', (int) $b),
                'blog' => $this->startCoverWorkflow($chatId, $telegramUserId, 'blog.cover', (int) $b),
                default => $this->sendWelcome($chatId),
            };

            return;
        }

        if ($verb === 'setbrand') {
            $this->startBrandImageWorkflow($chatId, $telegramUserId, $a === 'hero' ? 'hero' : 'logo');

            return;
        }

        if ($verb === 'removebrand') {
            $this->confirmRemoveBrand($chatId, $a === 'hero' ? 'hero' : 'logo');

            return;
        }

        if ($verb === 'confirmremovebrand') {
            $this->applyRemoveBrand($chatId, $a === 'hero' ? 'hero' : 'logo');

            return;
        }

        if ($verb === 'stats') {
            $this->sendStats($chatId);

            return;
        }

        if ($verb === 'activity') {
            $this->sendActivityLog($chatId);

            return;
        }

        if ($verb === 'userstatus') {
            $this->confirmUserStatus($chatId, (int) $a);

            return;
        }

        if ($verb === 'confirmuserstatus') {
            $this->applyUserStatus($chatId, (int) $a);

            return;
        }

        if ($verb === 'useradmin') {
            $this->confirmUserAdmin($chatId, (int) $a);

            return;
        }

        if ($verb === 'confirmuseradmin') {
            $this->applyUserAdmin($chatId, (int) $a);

            return;
        }

        if ($verb === 'userenroll') {
            $this->pickCourseForEnrollment($chatId, (int) $a);

            return;
        }

        if ($verb === 'confirmenroll') {
            $this->applyEnrollment($chatId, (int) $a, (int) $b);

            return;
        }

        if ($verb === 'userunenroll') {
            $this->pickEnrollmentForRemoval($chatId, (int) $a);

            return;
        }

        if ($verb === 'confirmunenroll') {
            $this->applyUnenrollment($chatId, (int) $a, (int) $b);

            return;
        }

        if ($verb === 'subjectvisibility') {
            $this->confirmSubjectVisibility($chatId, (int) $a);

            return;
        }

        if ($verb === 'confirmsubjectvisibility') {
            $this->applySubjectVisibility($chatId, (int) $a);

            return;
        }

        if ($verb === 'delete') {
            $this->confirmDelete($chatId, (string) $a, (int) $b);

            return;
        }

        if ($verb === 'confirmdelete') {
            $this->deleteEntity($chatId, (string) $a, (int) $b);

            return;
        }

        if ($verb === 'transition') {
            $this->confirmTransition($chatId, (string) $a, (int) $b, (string) $c);

            return;
        }

        if ($verb === 'confirmtransition') {
            $this->applyTransition($chatId, (string) $a, (int) $b, (string) $c);

            return;
        }

        if ($verb === 'cancel') {
            $this->cancelWorkflow($chatId);

            return;
        }

        if ($verb === 'backup') {
            $this->backupDatabase($chatId);

            return;
        }

        $this->sendWelcome($chatId);
    }

    private function handleTextInput(int $chatId, int $telegramUserId, string $text): bool
    {
        if ($this->handleMenuShortcut($chatId, $text)) {
            return true;
        }

        if (! str_starts_with($text, '/')) {
            return false;
        }

        [$command, $argument] = $this->parseCommand($text);

        return match ($command) {
            'start', 'help', 'menu' => $this->sendWelcome($chatId),
            'cancel' => $this->cancelWorkflow($chatId),
            'plans' => $this->listPlans($chatId),
            'plan_new' => $this->startPlanWorkflow($chatId, $telegramUserId, null),
            'plan_edit' => $this->startPlanWorkflow($chatId, $telegramUserId, $this->requiredId($argument, 'شناسه پلن را وارد کنید.')),
            'blogs' => $this->listBlogs($chatId),
            'blog_new' => $this->startBlogWorkflow($chatId, $telegramUserId, null),
            'blog_edit' => $this->startBlogWorkflow($chatId, $telegramUserId, $this->requiredId($argument, 'شناسه مطلب وبلاگ را وارد کنید.')),
            'subjects' => $this->listSubjects($chatId),
            'subject_new' => $this->startSubjectWorkflow($chatId, $telegramUserId, null),
            'subject_edit' => $this->startSubjectWorkflow($chatId, $telegramUserId, $this->requiredId($argument, 'شناسه شاخه را وارد کنید.')),
            'courses' => $this->listCourses($chatId),
            'course_new' => $this->startCourseWorkflow($chatId, $telegramUserId, null),
            'course_edit' => $this->startCourseWorkflow($chatId, $telegramUserId, $this->requiredId($argument, 'شناسه دوره را وارد کنید.')),
            'videos' => $this->listVideos($chatId),
            'video_new' => $this->startVideoWorkflow($chatId, $telegramUserId, null),
            'video_edit' => $this->startVideoWorkflow($chatId, $telegramUserId, $this->requiredId($argument, 'شناسه ویدیو را وارد کنید.')),
            'notes' => $this->listNotes($chatId),
            'note_new' => $this->startNoteWorkflow($chatId, $telegramUserId, null),
            'note_edit' => $this->startNoteWorkflow($chatId, $telegramUserId, $this->requiredId($argument, 'شناسه جزوه را وارد کنید.')),
            'decks' => $this->listDecks($chatId),
            'deck_new' => $this->startDeckWorkflow($chatId, $telegramUserId, null),
            'deck_edit' => $this->startDeckWorkflow($chatId, $telegramUserId, $this->requiredId($argument, 'شناسه دِک را وارد کنید.')),
            'card_new' => $this->startCardWorkflow($chatId, $telegramUserId, $argument !== '' ? ['deck_id' => (int) $argument] : []),
            'card_edit' => $this->startCardEditWorkflow($chatId, $telegramUserId, $this->requiredId($argument, 'شناسه فلش‌کارت را وارد کنید.')),
            'quizzes' => $this->listQuizzes($chatId),
            'quiz_new' => $this->startQuizWorkflow($chatId, $telegramUserId, null),
            'quiz_edit' => $this->startQuizWorkflow($chatId, $telegramUserId, $this->requiredId($argument, 'شناسه آزمون را وارد کنید.')),
            'question_new' => $this->startQuestionWorkflow($chatId, $telegramUserId, $argument !== '' ? ['quiz_id' => (int) $argument] : []),
            'question_edit' => $this->startQuestionEditWorkflow($chatId, $telegramUserId, $this->requiredId($argument, 'شناسه سؤال را وارد کنید.')),
            'set_course_cover' => $this->startCoverWorkflow($chatId, $telegramUserId, 'course.cover', $this->requiredId($argument, 'شناسه دوره را وارد کنید.')),
            'set_blog_cover' => $this->startCoverWorkflow($chatId, $telegramUserId, 'blog.cover', $this->requiredId($argument, 'شناسه مطلب وبلاگ را وارد کنید.')),
            'users' => $this->listUsers($chatId, 1),
            'user' => $this->showUserManageMenu($chatId, $this->requiredId($argument, 'شناسه کاربر را وارد کنید.')),
            'stats' => $this->sendStats($chatId),
            'activity' => $this->sendActivityLog($chatId),
            'set_logo' => $this->startBrandImageWorkflow($chatId, $telegramUserId, 'logo'),
            'remove_logo' => $this->confirmRemoveBrand($chatId, 'logo'),
            'set_hero' => $this->startBrandImageWorkflow($chatId, $telegramUserId, 'hero'),
            'backup_db' => $this->backupDatabase($chatId),
            default => $this->sendUnknownCommand($chatId),
        };
    }

    private function handleMenuShortcut(int $chatId, string $text): bool
    {
        return match ($text) {
            'راهنما' => $this->sendWelcome($chatId),
            'پلن‌ها' => $this->sendPlansHelp($chatId),
            'دوره‌ها' => $this->sendCoursesHelp($chatId),
            'بلاگ' => $this->sendBlogsHelp($chatId),
            'ویدیو و جزوه' => $this->sendMediaHelp($chatId),
            'آزمون و فلش‌کارت' => $this->sendStudyHelp($chatId),
            'کاربران' => $this->sendUsersHelp($chatId),
            'برند و ظاهر' => $this->sendBrandHelp($chatId),
            'آمار سایت' => $this->sendStats($chatId),
            'بکاپ دیتابیس' => $this->backupDatabase($chatId),
            default => false,
        };
    }

    private function handleWorkflow(TelegramChatSession $session, array $message): void
    {
        $workflow = (string) $session->workflow;

        match ($workflow) {
            'plan.form' => $this->submitPlanForm($session, (string) ($message['text'] ?? '')),
            'blog.form' => $this->submitBlogForm($session, (string) ($message['text'] ?? '')),
            'subject.form' => $this->submitSubjectForm($session, (string) ($message['text'] ?? '')),
            'course.form' => $this->submitCourseForm($session, (string) ($message['text'] ?? '')),
            'video.form' => $this->submitVideoForm($session, $message),
            'note.form' => $this->submitNoteForm($session, $message),
            'deck.form' => $this->submitDeckForm($session, (string) ($message['text'] ?? '')),
            'card.form' => $this->submitCardForm($session, (string) ($message['text'] ?? '')),
            'quiz.form' => $this->submitQuizForm($session, (string) ($message['text'] ?? '')),
            'question.form' => $this->submitQuestionForm($session, (string) ($message['text'] ?? '')),
            'course.cover' => $this->submitCourseCover($session, $message),
            'blog.cover' => $this->submitBlogCover($session, $message),
            'brand.logo', 'brand.hero' => $this->submitBrandImage($session, $message),
            default => throw new RuntimeException('گردش‌کار شناخته نشد. /cancel را بزنید و دوباره شروع کنید.'),
        };
    }

    private function sendWelcome(int $chatId): bool
    {
        $this->api->sendMessage($chatId, implode("\n", [
            '🤖 پنل مدیریت تلگرام بروکا',
            '',
            'از دکمه‌های زیر برای مدیریت استفاده کنید.',
            'برای ورود اطلاعات هر بخش، ربات بعد از انتخاب شما یک فرم یا مرحله راهنما می‌فرستد.',
            '',
            '📌 قابلیت‌ها:',
            '• پلن‌ها، وبلاگ، دوره‌ها، شاخه‌ها، ویدیو، جزوه، فلش‌کارت و آزمون',
            '• انتشار مرحله‌ای (پیش‌نویس ← بازبینی ← انتشار ← آرشیو)',
            '• مدیریت کاربران (تعلیق، مدیر، ثبت‌نام در دوره)',
            '• تغییر لوگو و تصویر هیرو صفحهٔ اصلی',
            '• آمار سایت، آخرین فعالیت‌های مدیران و بکاپ دیتابیس',
        ]), [
            'reply_markup' => $this->mainMenuMarkup(),
        ]);

        return true;
    }

    private function sendPlansHelp(int $chatId): bool
    {
        $this->api->sendMessage($chatId, 'مدیریت پلن‌ها و محصولات اشتراکی.', [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('📋 لیست پلن‌ها', 'list:plans'), $this->button('➕ پلن جدید', 'new:plan')],
                [$this->button('↩️ بازگشت', 'menu:main')],
            ]),
        ]);

        return true;
    }

    private function sendBlogsHelp(int $chatId): bool
    {
        $this->api->sendMessage($chatId, 'مدیریت وبلاگ، تصویر شاخص و انتشار مقاله.', [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('📋 لیست مقاله‌ها', 'list:blogs'), $this->button('➕ مقاله جدید', 'new:blog')],
                [$this->button('↩️ بازگشت', 'menu:main')],
            ]),
        ]);

        return true;
    }

    private function sendCoursesHelp(int $chatId): bool
    {
        $this->api->sendMessage($chatId, 'مدیریت دوره‌ها و شاخه‌های آموزشی (ساخت، ویرایش، انتشار، حذف و کاور).', [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('📚 لیست شاخه‌ها', 'list:subjects'), $this->button('🎓 لیست دوره‌ها', 'list:courses')],
                [$this->button('➕ دوره جدید', 'new:course'), $this->button('➕ شاخه جدید', 'new:subject')],
                [$this->button('↩️ بازگشت', 'menu:main')],
            ]),
        ]);

        return true;
    }

    private function sendMediaHelp(int $chatId): bool
    {
        $this->api->sendMessage($chatId, 'مدیریت ویدیوها و جزوه‌ها. برای ویدیو و جزوه می‌توانید فایل را مستقیم بفرستید یا آدرس URL بدهید.', [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('🎥 لیست ویدیوها', 'list:videos'), $this->button('📄 لیست جزوه‌ها', 'list:notes')],
                [$this->button('➕ ویدیو جدید', 'new:video'), $this->button('➕ جزوه جدید', 'new:note')],
                [$this->button('↩️ بازگشت', 'menu:main')],
            ]),
        ]);

        return true;
    }

    private function sendStudyHelp(int $chatId): bool
    {
        $this->api->sendMessage($chatId, 'مدیریت فلش‌کارت‌ها، آزمون‌ها و سؤال‌ها.', [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('🗂 لیست دِک‌ها', 'list:decks'), $this->button('🧪 لیست آزمون‌ها', 'list:quizzes')],
                [$this->button('➕ دِک جدید', 'new:deck'), $this->button('➕ آزمون جدید', 'new:quiz')],
                [$this->button('↩️ بازگشت', 'menu:main')],
            ]),
        ]);

        return true;
    }

    private function sendUsersHelp(int $chatId): bool
    {
        $this->api->sendMessage($chatId, implode("\n", [
            'مدیریت کاربران سایت:',
            '• لیست کاربران با صفحه‌بندی و کارت کامل هر کاربر',
            '• تعلیق / فعال‌سازی حساب (فوراً از نشست خارج می‌شود)',
            '• ارتقا به مدیر یا سلب مدیریت (با محافظ آخرین مدیر)',
            '• ثبت‌نام کاربر در دوره یا لغو ثبت‌نام',
            '',
            'دستور مستقیم: /user {شناسه}',
        ]), [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('👤 لیست کاربران', 'list:users:1')],
                [$this->button('↩️ بازگشت', 'menu:main')],
            ]),
        ]);

        return true;
    }

    private function sendBrandHelp(int $chatId): bool
    {
        $this->api->sendMessage($chatId, implode("\n", [
            'برند و ظاهر سایت:',
            '🖼 لوگو — بالای سایت و فوتر را جایگزین حرف «ب» می‌کند (PNG شفاف بهترین نتیجه را می‌دهد).',
            '🌄 هیرو — تصویر بزرگ صفحهٔ اصلی؛ نسخهٔ WebP به‌صورت خودکار بازسازی می‌شود.',
        ]), [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('🖼 تغییر لوگو', 'setbrand:logo'), $this->button('🌄 تغییر هیرو', 'setbrand:hero')],
                [$this->button('🗑 حذف لوگو (بازگشت به «ب»)', 'removebrand:logo')],
                [$this->button('↩️ بازگشت', 'menu:main')],
            ]),
        ]);

        return true;
    }

    private function sendUnknownCommand(int $chatId): bool
    {
        $this->api->sendMessage($chatId, 'این دستور را نمی‌شناسم. از منوی دکمه‌ای استفاده کنید.', [
            'reply_markup' => $this->mainMenuMarkup(),
        ]);

        return true;
    }

    private function cancelWorkflow(int $chatId): bool
    {
        TelegramChatSession::query()->where('telegram_chat_id', $chatId)->delete();
        $this->api->sendMessage($chatId, '❌ عملیات فعلی لغو شد.', [
            'reply_markup' => $this->mainMenuMarkup(),
        ]);

        return true;
    }

    private function listPlans(int $chatId): bool
    {
        $plans = Plan::query()->orderBy('sort_order')->orderBy('id')->get();
        $lines = ['📦 پلن‌های فعلی:'];
        $buttons = [
            [$this->button('➕ پلن جدید', 'new:plan'), $this->button('↩️ منوی پلن‌ها', 'menu:plans')],
        ];

        foreach ($plans as $plan) {
            $lines[] = sprintf('#%d | %s | %s | %s ماه | %s ریال', $plan->id, $plan->code, $plan->name, $plan->duration_months, number_format((int) $plan->price_irr));
            $buttons[] = [$this->button('🛠 پلن #'.$plan->id.' — '.$this->truncate($plan->name, 28), 'manage:plan:'.$plan->id)];
        }

        $this->api->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => $this->inlineKeyboard($buttons),
        ]);

        return true;
    }

    private function listBlogs(int $chatId): bool
    {
        $posts = BlogPost::query()->latest('id')->limit(12)->get();
        $lines = ['📝 مطالب وبلاگ:'];
        $buttons = [
            [$this->button('➕ مقاله جدید', 'new:blog'), $this->button('↩️ منوی وبلاگ', 'menu:blogs')],
        ];

        foreach ($posts as $post) {
            $lines[] = sprintf('#%d | %s | %s | /blog/%s', $post->id, $post->status, $post->title, $post->slug);
            $buttons[] = [$this->button('🛠 مقاله #'.$post->id.' — '.$this->truncate($post->title, 28), 'manage:blog:'.$post->id)];
        }

        if (count($lines) === 1) {
            $lines[] = 'هنوز مطلبی ثبت نشده است.';
        }

        $this->api->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => $this->inlineKeyboard($buttons),
        ]);

        return true;
    }

    private function listSubjects(int $chatId): bool
    {
        $subjects = Subject::query()->withCount('courses')->orderBy('sort_order')->orderBy('name')->get();
        $lines = ['📚 شاخه‌های آموزشی:'];
        $buttons = [
            [$this->button('➕ شاخه جدید', 'new:subject'), $this->button('🎓 لیست دوره‌ها', 'list:courses')],
        ];

        foreach ($subjects as $subject) {
            $lines[] = sprintf('#%d | %s | %d دوره%s', $subject->id, $subject->name, $subject->courses_count, $subject->is_visible ? '' : ' | پنهان');
            $buttons[] = [$this->button('🛠 '.$this->truncate($subject->name, 28), 'manage:subject:'.$subject->id)];
        }

        if ($subjects->isEmpty()) {
            $lines[] = 'هنوز شاخه‌ای ثبت نشده است.';
        }

        $this->api->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => $this->inlineKeyboard($buttons),
        ]);

        return true;
    }

    private function listCourses(int $chatId): bool
    {
        $courses = Course::query()->with('subject')->latest('id')->limit(12)->get();
        $lines = ['🎓 دوره‌ها:'];
        $buttons = [
            [$this->button('➕ دوره جدید', 'new:course'), $this->button('↩️ منوی دوره‌ها', 'menu:courses')],
        ];

        foreach ($courses as $course) {
            $lines[] = sprintf('#%d | %s | %s', $course->id, $course->subject?->name ?? 'بدون درس', $course->title);
            $buttons[] = [$this->button('🛠 دوره #'.$course->id.' — '.$this->truncate($course->title, 28), 'manage:course:'.$course->id)];
        }

        if (count($lines) === 1) {
            $lines[] = 'هنوز دوره‌ای ثبت نشده است.';
        }

        $this->api->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => $this->inlineKeyboard($buttons),
        ]);

        return true;
    }

    private function listVideos(int $chatId): bool
    {
        $videos = Video::query()->with('course')->latest('id')->limit(12)->get();
        $lines = ['🎥 ویدیوها:'];
        $buttons = [
            [$this->button('➕ ویدیو جدید', 'new:video'), $this->button('↩️ منوی رسانه', 'menu:media')],
        ];

        foreach ($videos as $video) {
            $lines[] = sprintf('#%d | %s | %s', $video->id, $video->course?->title ?? 'بدون دوره', $video->title);
            $buttons[] = [$this->button('🛠 ویدیو #'.$video->id.' — '.$this->truncate($video->title, 28), 'manage:video:'.$video->id)];
        }

        if (count($lines) === 1) {
            $lines[] = 'هنوز ویدیویی ثبت نشده است.';
        }

        $this->api->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => $this->inlineKeyboard($buttons),
        ]);

        return true;
    }

    private function listNotes(int $chatId): bool
    {
        $notes = Note::query()->with('course')->latest('id')->limit(12)->get();
        $lines = ['📄 جزوه‌ها:'];
        $buttons = [
            [$this->button('➕ جزوه جدید', 'new:note'), $this->button('↩️ منوی رسانه', 'menu:media')],
        ];

        foreach ($notes as $note) {
            $lines[] = sprintf('#%d | %s | %s', $note->id, $note->course?->title ?? 'بدون دوره', $note->title);
            $buttons[] = [$this->button('🛠 جزوه #'.$note->id.' — '.$this->truncate($note->title, 28), 'manage:note:'.$note->id)];
        }

        if (count($lines) === 1) {
            $lines[] = 'هنوز جزوه‌ای ثبت نشده است.';
        }

        $this->api->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => $this->inlineKeyboard($buttons),
        ]);

        return true;
    }

    private function listDecks(int $chatId): bool
    {
        $decks = FlashcardDeck::query()->with('course')->latest('id')->limit(12)->get();
        $lines = ['🗂 دِک‌های فلش‌کارت:'];
        $buttons = [
            [$this->button('➕ دِک جدید', 'new:deck'), $this->button('↩️ منوی آزمون/فلش‌کارت', 'menu:study')],
        ];

        foreach ($decks as $deck) {
            $lines[] = sprintf('#%d | %s | %s', $deck->id, $deck->course?->title ?? 'بدون دوره', $deck->title);
            $buttons[] = [$this->button('🛠 دِک #'.$deck->id.' — '.$this->truncate($deck->title, 28), 'manage:deck:'.$deck->id)];
        }

        if (count($lines) === 1) {
            $lines[] = 'هنوز دِکی ثبت نشده است.';
        }

        $this->api->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => $this->inlineKeyboard($buttons),
        ]);

        return true;
    }

    private function listQuizzes(int $chatId): bool
    {
        $quizzes = Quiz::query()->with('course')->latest('id')->limit(12)->get();
        $lines = ['🧪 آزمون‌ها:'];
        $buttons = [
            [$this->button('➕ آزمون جدید', 'new:quiz'), $this->button('↩️ منوی آزمون/فلش‌کارت', 'menu:study')],
        ];

        foreach ($quizzes as $quiz) {
            $lines[] = sprintf('#%d | %s | %s', $quiz->id, $quiz->course?->title ?? 'بدون دوره', $quiz->title);
            $buttons[] = [$this->button('🛠 آزمون #'.$quiz->id.' — '.$this->truncate($quiz->title, 28), 'manage:quiz:'.$quiz->id)];
        }

        if (count($lines) === 1) {
            $lines[] = 'هنوز آزمونی ثبت نشده است.';
        }

        $this->api->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => $this->inlineKeyboard($buttons),
        ]);

        return true;
    }

    private function listCards(int $chatId, int $deckId): bool
    {
        $deck = FlashcardDeck::query()->findOrFail($deckId);
        $cards = Flashcard::query()->where('flashcard_deck_id', $deckId)->orderBy('sort_order')->latest('id')->limit(12)->get();
        $lines = ['🃏 کارت‌های دِک: '.$deck->title];
        $buttons = [
            [$this->button('➕ فلش‌کارت جدید', 'new:card:'.$deckId), $this->button('↩️ بازگشت به دِک', 'manage:deck:'.$deckId)],
        ];

        foreach ($cards as $card) {
            $lines[] = sprintf('#%d | %s', $card->id, $this->truncate(trim((string) $card->front), 60));
            $buttons[] = [$this->button('🛠 کارت #'.$card->id, 'manage:card:'.$card->id)];
        }

        if ($cards->isEmpty()) {
            $lines[] = 'هنوز فلش‌کارتی ثبت نشده است.';
        }

        $this->api->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => $this->inlineKeyboard($buttons),
        ]);

        return true;
    }

    private function listQuestions(int $chatId, int $quizId): bool
    {
        $quiz = Quiz::query()->findOrFail($quizId);
        $questions = QuizQuestion::query()->where('quiz_id', $quizId)->orderBy('sort_order')->latest('id')->limit(12)->get();
        $lines = ['❓ سؤال‌های آزمون: '.$quiz->title];
        $buttons = [
            [$this->button('➕ سؤال جدید', 'new:question:'.$quizId), $this->button('↩️ بازگشت به آزمون', 'manage:quiz:'.$quizId)],
        ];

        foreach ($questions as $question) {
            $lines[] = sprintf('#%d | %s', $question->id, $this->truncate(trim((string) $question->prompt), 60));
            $buttons[] = [$this->button('🛠 سؤال #'.$question->id, 'manage:question:'.$question->id)];
        }

        if ($questions->isEmpty()) {
            $lines[] = 'هنوز سؤالی ثبت نشده است.';
        }

        $this->api->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => $this->inlineKeyboard($buttons),
        ]);

        return true;
    }

    private function startPlanWorkflow(int $chatId, int $telegramUserId, ?int $id): bool
    {
        $plan = $id ? Plan::query()->findOrFail($id) : null;

        $this->storeSession($chatId, $telegramUserId, 'plan.form', ['id' => $plan?->id]);
        $this->api->sendMessage($chatId, $this->planTemplate($plan), [
            'reply_markup' => $this->workflowMarkup('menu:plans'),
        ]);

        return true;
    }

    private function startBlogWorkflow(int $chatId, int $telegramUserId, ?int $id): bool
    {
        $post = $id ? BlogPost::query()->findOrFail($id) : null;

        $this->storeSession($chatId, $telegramUserId, 'blog.form', ['id' => $post?->id]);
        $this->api->sendMessage($chatId, $this->blogTemplate($post), [
            'reply_markup' => $this->workflowMarkup($post ? 'manage:blog:'.$post->id : 'menu:blogs'),
        ]);

        return true;
    }

    private function startCourseWorkflow(int $chatId, int $telegramUserId, ?int $id): bool
    {
        $course = $id ? Course::query()->findOrFail($id) : null;

        $this->storeSession($chatId, $telegramUserId, 'course.form', ['id' => $course?->id]);
        $this->api->sendMessage($chatId, $this->courseTemplate($course), [
            'reply_markup' => $this->workflowMarkup($course ? 'manage:course:'.$course->id : 'menu:courses'),
        ]);

        return true;
    }

    private function startVideoWorkflow(int $chatId, int $telegramUserId, ?int $id): bool
    {
        $video = $id ? Video::query()->findOrFail($id) : null;

        $this->storeSession($chatId, $telegramUserId, 'video.form', ['id' => $video?->id]);
        $this->api->sendMessage($chatId, $this->videoTemplate($video), [
            'reply_markup' => $this->workflowMarkup($video ? 'manage:video:'.$video->id : 'menu:media'),
        ]);

        return true;
    }

    private function startNoteWorkflow(int $chatId, int $telegramUserId, ?int $id): bool
    {
        $note = $id ? Note::query()->findOrFail($id) : null;

        $this->storeSession($chatId, $telegramUserId, 'note.form', ['id' => $note?->id]);
        $this->api->sendMessage($chatId, $this->noteTemplate($note), [
            'reply_markup' => $this->workflowMarkup($note ? 'manage:note:'.$note->id : 'menu:media'),
        ]);

        return true;
    }

    private function startDeckWorkflow(int $chatId, int $telegramUserId, ?int $id): bool
    {
        $deck = $id ? FlashcardDeck::query()->findOrFail($id) : null;

        $this->storeSession($chatId, $telegramUserId, 'deck.form', ['id' => $deck?->id]);
        $this->api->sendMessage($chatId, $this->deckTemplate($deck), [
            'reply_markup' => $this->workflowMarkup($deck ? 'manage:deck:'.$deck->id : 'menu:study'),
        ]);

        return true;
    }

    private function startCardWorkflow(int $chatId, int $telegramUserId, array $context = []): bool
    {
        $this->storeSession($chatId, $telegramUserId, 'card.form', $context);
        $back = ! empty($context['deck_id']) ? 'list:cards:'.$context['deck_id'] : 'menu:study';
        $this->api->sendMessage($chatId, $this->cardTemplate(null, $context['deck_id'] ?? null), [
            'reply_markup' => $this->workflowMarkup($back),
        ]);

        return true;
    }

    private function startCardEditWorkflow(int $chatId, int $telegramUserId, int $id): bool
    {
        $card = Flashcard::query()->findOrFail($id);

        $this->storeSession($chatId, $telegramUserId, 'card.form', ['id' => $card->id]);
        $this->api->sendMessage($chatId, $this->cardTemplate($card, $card->flashcard_deck_id), [
            'reply_markup' => $this->workflowMarkup('manage:card:'.$card->id),
        ]);

        return true;
    }

    private function startQuizWorkflow(int $chatId, int $telegramUserId, ?int $id): bool
    {
        $quiz = $id ? Quiz::query()->findOrFail($id) : null;

        $this->storeSession($chatId, $telegramUserId, 'quiz.form', ['id' => $quiz?->id]);
        $this->api->sendMessage($chatId, $this->quizTemplate($quiz), [
            'reply_markup' => $this->workflowMarkup($quiz ? 'manage:quiz:'.$quiz->id : 'menu:study'),
        ]);

        return true;
    }

    private function startQuestionWorkflow(int $chatId, int $telegramUserId, array $context = []): bool
    {
        $this->storeSession($chatId, $telegramUserId, 'question.form', $context);
        $back = ! empty($context['quiz_id']) ? 'list:questions:'.$context['quiz_id'] : 'menu:study';
        $this->api->sendMessage($chatId, $this->questionTemplate(null, $context['quiz_id'] ?? null), [
            'reply_markup' => $this->workflowMarkup($back),
        ]);

        return true;
    }

    private function startQuestionEditWorkflow(int $chatId, int $telegramUserId, int $id): bool
    {
        $question = QuizQuestion::query()->with('options')->findOrFail($id);

        $this->storeSession($chatId, $telegramUserId, 'question.form', ['id' => $question->id]);
        $this->api->sendMessage($chatId, $this->questionTemplate($question, $question->quiz_id), [
            'reply_markup' => $this->workflowMarkup('manage:question:'.$question->id),
        ]);

        return true;
    }

    private function startCoverWorkflow(int $chatId, int $telegramUserId, string $workflow, int $id): bool
    {
        $this->storeSession($chatId, $telegramUserId, $workflow, ['id' => $id]);
        $back = $workflow === 'blog.cover' ? 'manage:blog:'.$id : 'manage:course:'.$id;
        $this->api->sendMessage($chatId, 'تصویر را به صورت photo یا document بفرستید.', [
            'reply_markup' => $this->workflowMarkup($back),
        ]);

        return true;
    }

    private function submitPlanForm(TelegramChatSession $session, string $text): void
    {
        $data = StructuredMessageParser::parse($text);
        $plan = $this->resolveOptionalPlan($session, $data);

        $validated = Validator::make([
            'code' => $data['code'] ?? $plan?->code,
            'name' => $data['name'] ?? $plan?->name,
            'description' => $data['description'] ?? $plan?->description,
            'price_irr' => $this->nullableInt($data['price_irr'] ?? $plan?->price_irr),
            'duration_months' => $this->nullableInt($data['duration_months'] ?? $plan?->duration_months),
            'sort_order' => $this->nullableInt($data['sort_order'] ?? $plan?->sort_order ?? 0),
            'is_active' => $this->nullableBool($data['is_active'] ?? ($plan?->is_active ? '1' : '0')),
        ], [
            'code' => ['required', 'string', 'max:120', Rule::unique('plans', 'code')->ignore($plan?->id)],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price_irr' => ['required', 'integer', 'min:0', 'max:2000000000'],
            'duration_months' => ['required', 'integer', 'min:0', 'max:36'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['required', 'boolean'],
        ])->validate();

        if ($plan) {
            $plan->update($validated);
        } else {
            Plan::create($validated);
        }

        $this->finish($session, '✅ پلن با موفقیت ذخیره شد.');
    }

    private function submitBlogForm(TelegramChatSession $session, string $text): void
    {
        $data = StructuredMessageParser::parse($text);
        $post = $this->resolveOptionalBlogPost($session, $data);

        $validated = Validator::make([
            'title' => $data['title'] ?? $post?->title,
            'category' => $this->nullableString($data['category'] ?? $post?->category),
            'author_name' => $this->nullableString($data['author_name'] ?? $post?->author_name),
            'reviewer_name' => $this->nullableString($data['reviewer_name'] ?? $post?->reviewer_name),
            'excerpt' => $this->nullableString($data['excerpt'] ?? $post?->excerpt),
            'content' => $data['content'] ?? $post?->content,
            'cover_image_path' => $this->nullableString($data['cover_image_path'] ?? $post?->cover_image_path),
            'status' => $data['status'] ?? $post?->status ?? 'draft',
            'published_at' => $this->nullableDate($data['published_at'] ?? $post?->published_at),
            'meta_title' => $this->nullableString($data['meta_title'] ?? $post?->meta_title),
            'meta_description' => $this->nullableString($data['meta_description'] ?? $post?->meta_description),
        ], [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'author_name' => ['nullable', 'string', 'max:120'],
            'reviewer_name' => ['nullable', 'string', 'max:120'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'string'],
            'cover_image_path' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ])->validate();

        $this->ensureManualPublishNotAllowed('مقاله', $post?->status, $validated['status']);

        $slug = $post?->slug;
        if (! $post || $validated['title'] !== $post->title) {
            $slug = Slug::unique($validated['title'], fn (string $candidate): bool => BlogPost::query()
                ->when($post, fn ($query) => $query->where('id', '!=', $post->id))
                ->where('slug', $candidate)
                ->exists());
        }

        $validated['slug'] = $slug;

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        if ($post) {
            $this->assertPublicationTransition($post->status, $validated['status']);
            $post->update($validated);
        } else {
            BlogPost::create($validated);
        }

        $this->finish($session, '✅ مطلب وبلاگ ذخیره شد.');
    }

    private function submitCourseForm(TelegramChatSession $session, string $text): void
    {
        $data = StructuredMessageParser::parse($text);
        $course = $this->resolveOptionalCourse($session, $data);

        $validated = Validator::make([
            'subject_id' => $this->nullableInt($data['subject_id'] ?? $course?->subject_id),
            'title' => $data['title'] ?? $course?->title,
            'excerpt' => $this->nullableString($data['excerpt'] ?? $course?->excerpt),
            'description' => $data['description'] ?? $course?->description,
            'cover_image_path' => $this->nullableString($data['cover_image_path'] ?? $course?->cover_image_path),
            'level' => $this->nullableString($data['level'] ?? $course?->level),
            'sort_order' => $this->nullableInt($data['sort_order'] ?? $course?->sort_order ?? 0),
            'author_id' => $this->nullableInt($data['author_id'] ?? $course?->author_id),
            'reviewer_id' => $this->nullableInt($data['reviewer_id'] ?? $course?->reviewer_id),
            'status' => $data['status'] ?? $course?->status ?? 'draft',
            'published_at' => $this->nullableDate($data['published_at'] ?? $course?->published_at),
        ], [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'cover_image_path' => ['nullable', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'author_id' => ['nullable', 'integer', 'exists:contributors,id'],
            'reviewer_id' => ['nullable', 'integer', 'exists:contributors,id', 'different:author_id'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'published_at' => ['nullable', 'date'],
        ])->validate();

        $this->ensureManualPublishNotAllowed('دوره', $course?->status, $validated['status']);
        $this->assertBylinesIfPublishing('courses', $validated['status'], $validated['author_id'], $validated['reviewer_id']);

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        if ($course) {
            $this->assertPublicationTransition($course->status, $validated['status']);
            $titleChanged = $validated['title'] !== $course->title;
            $course->fill($validated);
            if ($titleChanged) {
                $course->slug = Slug::unique($validated['title'], fn (string $candidate): bool => Course::query()->where('id', '!=', $course->id)->where('slug', $candidate)->exists());
            }
            $course->save();
        } else {
            $course = new Course($validated);
            $course->slug = Slug::unique($validated['title'], fn (string $candidate): bool => Course::query()->where('slug', $candidate)->exists());
            $course->save();
        }

        $this->finish($session, '✅ دوره ذخیره شد.');
    }

    private function submitVideoForm(TelegramChatSession $session, array $message): void
    {
        $formText = trim((string) ($message['caption'] ?? $message['text'] ?? ''));
        if ($formText === '') {
            throw new RuntimeException('قالب اطلاعات ویدیو را داخل کپشن فایل یا در متن پیام بفرستید.');
        }

        $data = StructuredMessageParser::parse($formText);
        $video = $this->resolveOptionalVideo($session, $data);
        $mode = strtolower($data['source_mode'] ?? (($message['video'] ?? $message['document']) ? 'upload' : (($data['playback_url'] ?? '') !== '' ? 'url' : ($video ? 'keep' : 'upload'))));

        $validated = Validator::make([
            'course_id' => $this->nullableInt($data['course_id'] ?? $video?->course_id),
            'title' => $data['title'] ?? $video?->title,
            'description' => $this->nullableString($data['description'] ?? $video?->description),
            'duration_seconds' => $this->nullableInt($data['duration_seconds'] ?? $video?->duration_seconds),
            'completion_threshold_percent' => $this->nullableInt($data['completion_threshold_percent'] ?? $video?->completion_threshold_percent),
            'sort_order' => $this->nullableInt($data['sort_order'] ?? $video?->sort_order ?? 0),
            'author_id' => $this->nullableInt($data['author_id'] ?? $video?->author_id),
            'reviewer_id' => $this->nullableInt($data['reviewer_id'] ?? $video?->reviewer_id),
            'status' => $data['status'] ?? $video?->status ?? 'draft',
            'published_at' => $this->nullableDate($data['published_at'] ?? $video?->published_at),
            'is_free_designated' => $this->nullableBool($data['is_free_designated'] ?? ($video?->is_free_designated ? '1' : '0')),
            'source_mode' => $mode,
            'playback_url' => $this->nullableString($data['playback_url'] ?? null),
        ], [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'completion_threshold_percent' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'author_id' => ['nullable', 'integer', 'exists:contributors,id'],
            'reviewer_id' => ['nullable', 'integer', 'exists:contributors,id', 'different:author_id'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'published_at' => ['nullable', 'date'],
            'is_free_designated' => ['required', 'boolean'],
            'source_mode' => ['required', 'in:upload,url,keep'],
            'playback_url' => ['nullable', 'url'],
        ])->validate();

        if (! $video && $validated['source_mode'] === 'keep') {
            throw new RuntimeException('برای ساخت ویدیو باید فایل ارسال کنید یا playback_url بدهید.');
        }

        $this->ensureManualPublishNotAllowed('ویدیو', $video?->status, $validated['status']);
        $this->assertBylinesIfPublishing('videos', $validated['status'], $validated['author_id'], $validated['reviewer_id']);

        $localAsset = null;
        if ($validated['source_mode'] === 'upload') {
            $localAsset = $this->storeTelegramVideo($message, $validated['title']);
        }

        if ($validated['source_mode'] === 'url' && empty($validated['playback_url']) && ! $video) {
            throw new RuntimeException('برای حالت url باید playback_url را وارد کنید.');
        }

        if ($validated['source_mode'] === 'url' && ! empty($validated['playback_url'])) {
            $this->assertExternalVideoOrigin($validated['playback_url']);
        }

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $payload = Arr::except($validated, ['is_free_designated', 'source_mode', 'playback_url']);

        if ($video) {
            $this->assertPublicationTransition($video->status, $validated['status']);
            $titleChanged = $payload['title'] !== $video->title;
            $courseChanged = (int) $payload['course_id'] !== (int) $video->course_id;
            $video->fill($payload);
            if ($titleChanged || $courseChanged) {
                $video->slug = Slug::unique($payload['title'], fn (string $candidate): bool => Video::query()
                    ->where('course_id', $payload['course_id'])
                    ->where('id', '!=', $video->id)
                    ->where('slug', $candidate)
                    ->exists());
            }
        } else {
            $video = new Video($payload);
            $video->slug = Slug::unique($payload['title'], fn (string $candidate): bool => Video::query()
                ->where('course_id', $payload['course_id'])
                ->where('slug', $candidate)
                ->exists());
        }

        if ($validated['source_mode'] === 'upload' && $localAsset) {
            $video->playback_provider = 'placeholder';
            $video->playback_asset_id = null;
            $video->manifest_reference = basename($localAsset);
        }

        if ($validated['source_mode'] === 'url' && ! empty($validated['playback_url'])) {
            $video->playback_provider = 'external';
            $video->playback_asset_id = $validated['playback_url'];
            $video->manifest_reference = null;
        }

        $video->save();
        $this->syncFreeDesignation($video, $validated['is_free_designated']);

        $this->finish($session, '✅ ویدیو ذخیره شد.');
    }

    private function submitNoteForm(TelegramChatSession $session, array $message): void
    {
        $formText = trim((string) ($message['caption'] ?? $message['text'] ?? ''));
        if ($formText === '') {
            throw new RuntimeException('قالب اطلاعات جزوه را در متن یا کپشن بفرستید.');
        }

        $data = StructuredMessageParser::parse($formText);
        $note = $this->resolveOptionalNote($session, $data);
        $mode = strtolower($data['source_mode'] ?? (($message['document'] ?? null) ? 'upload' : (($data['file_url'] ?? '') !== '' ? 'url' : ($note ? 'keep' : 'upload'))));

        $validated = Validator::make([
            'course_id' => $this->nullableInt($data['course_id'] ?? $note?->course_id),
            'title' => $data['title'] ?? $note?->title,
            'description' => $this->nullableString($data['description'] ?? $note?->description),
            'sort_order' => $this->nullableInt($data['sort_order'] ?? $note?->sort_order ?? 0),
            'author_id' => $this->nullableInt($data['author_id'] ?? $note?->author_id),
            'reviewer_id' => $this->nullableInt($data['reviewer_id'] ?? $note?->reviewer_id),
            'status' => $data['status'] ?? $note?->status ?? 'draft',
            'published_at' => $this->nullableDate($data['published_at'] ?? $note?->published_at),
            'is_free_designated' => $this->nullableBool($data['is_free_designated'] ?? ($note?->is_free_designated ? '1' : '0')),
            'source_mode' => $mode,
            'file_url' => $this->nullableString($data['file_url'] ?? null),
        ], [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'author_id' => ['nullable', 'integer', 'exists:contributors,id'],
            'reviewer_id' => ['nullable', 'integer', 'exists:contributors,id', 'different:author_id'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'published_at' => ['nullable', 'date'],
            'is_free_designated' => ['required', 'boolean'],
            'source_mode' => ['required', 'in:upload,url,keep'],
            'file_url' => ['nullable', 'url'],
        ])->validate();

        if (! $note && $validated['source_mode'] === 'keep') {
            throw new RuntimeException('برای ساخت جزوه باید فایل document ارسال کنید یا file_url بدهید.');
        }

        $this->ensureManualPublishNotAllowed('جزوه', $note?->status, $validated['status']);
        $this->assertBylinesIfPublishing('notes', $validated['status'], $validated['author_id'], $validated['reviewer_id']);

        if ($validated['source_mode'] === 'url' && empty($validated['file_url']) && ! $note) {
            throw new RuntimeException('برای حالت url باید file_url را وارد کنید.');
        }

        $stored = null;
        if ($validated['source_mode'] === 'upload') {
            $stored = $this->storeTelegramDocument($message, 'notes', $validated['title']);
        } elseif ($validated['source_mode'] === 'url' && ! empty($validated['file_url'])) {
            $stored = $this->storeRemoteDocument($validated['file_url'], 'notes', $validated['title']);
        }

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $payload = Arr::except($validated, ['is_free_designated', 'source_mode', 'file_url']);

        if ($note) {
            $this->assertPublicationTransition($note->status, $validated['status']);
            $titleChanged = $payload['title'] !== $note->title;
            $courseChanged = (int) $payload['course_id'] !== (int) $note->course_id;
            $note->fill($payload);
            if ($titleChanged || $courseChanged) {
                $note->slug = Slug::unique($payload['title'], fn (string $candidate): bool => Note::query()
                    ->where('course_id', $payload['course_id'])
                    ->where('id', '!=', $note->id)
                    ->where('slug', $candidate)
                    ->exists());
            }
        } else {
            $note = new Note($payload);
            $note->slug = Slug::unique($payload['title'], fn (string $candidate): bool => Note::query()
                ->where('course_id', $payload['course_id'])
                ->where('slug', $candidate)
                ->exists());
        }

        if ($stored) {
            $note->storage_disk = 'local';
            $note->storage_key = $stored['storage_key'];
            $note->mime_type = $stored['mime_type'];
            $note->size_bytes = $stored['size_bytes'];
            $note->checksum = $stored['checksum'];
        }

        $note->save();
        $this->syncFreeDesignation($note, $validated['is_free_designated']);

        $this->finish($session, '✅ جزوه ذخیره شد.');
    }

    private function submitDeckForm(TelegramChatSession $session, string $text): void
    {
        $data = StructuredMessageParser::parse($text);
        $deck = $this->resolveOptionalDeck($session, $data);

        $validated = Validator::make([
            'course_id' => $this->nullableInt($data['course_id'] ?? $deck?->course_id),
            'title' => $data['title'] ?? $deck?->title,
            'description' => $this->nullableString($data['description'] ?? $deck?->description),
            'sort_order' => $this->nullableInt($data['sort_order'] ?? $deck?->sort_order ?? 0),
            'author_id' => $this->nullableInt($data['author_id'] ?? $deck?->author_id),
            'reviewer_id' => $this->nullableInt($data['reviewer_id'] ?? $deck?->reviewer_id),
            'status' => $data['status'] ?? $deck?->status ?? 'draft',
            'published_at' => $this->nullableDate($data['published_at'] ?? $deck?->published_at),
        ], [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'author_id' => ['nullable', 'integer', 'exists:contributors,id'],
            'reviewer_id' => ['nullable', 'integer', 'exists:contributors,id', 'different:author_id'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'published_at' => ['nullable', 'date'],
        ])->validate();

        $this->ensureManualPublishNotAllowed('دِک فلش‌کارت', $deck?->status, $validated['status']);
        $this->assertBylinesIfPublishing('flashcard_decks', $validated['status'], $validated['author_id'], $validated['reviewer_id']);

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        if ($deck) {
            $this->assertPublicationTransition($deck->status, $validated['status']);
            $titleChanged = $validated['title'] !== $deck->title;
            $courseChanged = (int) $validated['course_id'] !== (int) $deck->course_id;
            $deck->fill($validated);
            if ($titleChanged || $courseChanged) {
                $deck->slug = Slug::unique($validated['title'], fn (string $candidate): bool => FlashcardDeck::query()
                    ->where('course_id', $validated['course_id'])
                    ->where('id', '!=', $deck->id)
                    ->where('slug', $candidate)
                    ->exists());
            }
            $deck->save();
        } else {
            $deck = new FlashcardDeck($validated);
            $deck->slug = Slug::unique($validated['title'], fn (string $candidate): bool => FlashcardDeck::query()->where('course_id', $validated['course_id'])->where('slug', $candidate)->exists());
            $deck->save();
        }

        $this->finish($session, '✅ دِک فلش‌کارت ذخیره شد.');
    }

    private function submitCardForm(TelegramChatSession $session, string $text): void
    {
        $data = StructuredMessageParser::parse($text);
        $card = $this->resolveOptionalCard($session, $data);

        $validated = Validator::make([
            'flashcard_deck_id' => $this->nullableInt($data['flashcard_deck_id'] ?? $session->context['deck_id'] ?? $card?->flashcard_deck_id),
            'front' => $data['front'] ?? $card?->front,
            'back' => $data['back'] ?? $card?->back,
            'hint' => $this->nullableString($data['hint'] ?? $card?->hint),
            'sort_order' => $this->nullableInt($data['sort_order'] ?? $card?->sort_order ?? 0),
            'status' => $data['status'] ?? $card?->status ?? 'draft',
            'published_at' => $this->nullableDate($data['published_at'] ?? $card?->published_at),
            'is_free_designated' => $this->nullableBool($data['is_free_designated'] ?? ($card?->is_free_designated ? '1' : '0')),
        ], [
            'flashcard_deck_id' => ['required', 'integer', 'exists:flashcard_decks,id'],
            'front' => ['required', 'string'],
            'back' => ['required', 'string'],
            'hint' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'published_at' => ['nullable', 'date'],
            'is_free_designated' => ['required', 'boolean'],
        ])->validate();

        $this->ensureManualPublishNotAllowed('فلش‌کارت', $card?->status, $validated['status']);

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $payload = Arr::except($validated, ['is_free_designated']);

        if ($card) {
            $this->assertPublicationTransition($card->status, $validated['status']);
            $card->fill($payload)->save();
        } else {
            $card = Flashcard::create($payload);
        }

        $this->syncFreeDesignation($card, $validated['is_free_designated']);
        $this->finish($session, '✅ فلش‌کارت ذخیره شد.');
    }

    private function submitQuizForm(TelegramChatSession $session, string $text): void
    {
        $data = StructuredMessageParser::parse($text);
        $quiz = $this->resolveOptionalQuiz($session, $data);

        $validated = Validator::make([
            'course_id' => $this->nullableInt($data['course_id'] ?? $quiz?->course_id),
            'title' => $data['title'] ?? $quiz?->title,
            'description' => $this->nullableString($data['description'] ?? $quiz?->description),
            'pass_threshold_percent' => $this->nullableInt($data['pass_threshold_percent'] ?? $quiz?->pass_threshold_percent),
            'sort_order' => $this->nullableInt($data['sort_order'] ?? $quiz?->sort_order ?? 0),
            'author_id' => $this->nullableInt($data['author_id'] ?? $quiz?->author_id),
            'reviewer_id' => $this->nullableInt($data['reviewer_id'] ?? $quiz?->reviewer_id),
            'status' => $data['status'] ?? $quiz?->status ?? 'draft',
            'published_at' => $this->nullableDate($data['published_at'] ?? $quiz?->published_at),
        ], [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'pass_threshold_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'author_id' => ['nullable', 'integer', 'exists:contributors,id'],
            'reviewer_id' => ['nullable', 'integer', 'exists:contributors,id', 'different:author_id'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'published_at' => ['nullable', 'date'],
        ])->validate();

        $this->ensureManualPublishNotAllowed('آزمون', $quiz?->status, $validated['status']);
        $this->assertBylinesIfPublishing('quizzes', $validated['status'], $validated['author_id'], $validated['reviewer_id']);

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        if ($quiz) {
            $this->assertPublicationTransition($quiz->status, $validated['status']);
            $titleChanged = $validated['title'] !== $quiz->title;
            $courseChanged = (int) $validated['course_id'] !== (int) $quiz->course_id;
            $quiz->fill($validated);
            if ($titleChanged || $courseChanged) {
                $quiz->slug = Slug::unique($validated['title'], fn (string $candidate): bool => Quiz::query()
                    ->where('course_id', $validated['course_id'])
                    ->where('id', '!=', $quiz->id)
                    ->where('slug', $candidate)
                    ->exists());
            }
            $quiz->save();
        } else {
            $quiz = new Quiz($validated);
            $quiz->slug = Slug::unique($validated['title'], fn (string $candidate): bool => Quiz::query()->where('course_id', $validated['course_id'])->where('slug', $candidate)->exists());
            $quiz->save();
        }

        $this->finish($session, '✅ آزمون ذخیره شد.');
    }

    private function submitQuestionForm(TelegramChatSession $session, string $text): void
    {
        $data = StructuredMessageParser::parse($text);
        $question = $this->resolveOptionalQuestion($session, $data);
        $options = collect(explode('|', (string) ($data['options'] ?? '')))
            ->map(fn (string $value): string => trim($value))
            ->filter()
            ->values()
            ->all();

        if ($question && empty($options)) {
            $options = $question->options()->orderBy('sort_order')->pluck('label')->all();
        }

        $validated = Validator::make([
            'quiz_id' => $this->nullableInt($data['quiz_id'] ?? $session->context['quiz_id'] ?? $question?->quiz_id),
            'prompt' => $data['prompt'] ?? $question?->prompt,
            'explanation' => $this->nullableString($data['explanation'] ?? $question?->explanation),
            'source_citation' => $this->nullableString($data['source_citation'] ?? $question?->source_citation),
            'sort_order' => $this->nullableInt($data['sort_order'] ?? $question?->sort_order ?? 0),
            'author_id' => $this->nullableInt($data['author_id'] ?? $question?->author_id),
            'reviewer_id' => $this->nullableInt($data['reviewer_id'] ?? $question?->reviewer_id),
            'status' => $data['status'] ?? $question?->status ?? 'draft',
            'published_at' => $this->nullableDate($data['published_at'] ?? $question?->published_at),
            'is_free_designated' => $this->nullableBool($data['is_free_designated'] ?? ($question?->is_free_designated ? '1' : '0')),
            'correct_index' => $this->nullableInt($data['correct_index'] ?? null),
            'options' => $options,
        ], [
            'quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
            'prompt' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'source_citation' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'author_id' => ['nullable', 'integer', 'exists:contributors,id'],
            'reviewer_id' => ['nullable', 'integer', 'exists:contributors,id', 'different:author_id'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'published_at' => ['nullable', 'date'],
            'is_free_designated' => ['required', 'boolean'],
            'correct_index' => ['required', 'integer', 'min:0'],
            'options' => ['required', 'array', 'min:2', 'max:6'],
            'options.*' => ['required', 'string'],
        ])->validate();

        if ((int) $validated['correct_index'] >= count($validated['options'])) {
            throw ValidationException::withMessages(['correct_index' => 'شماره گزینه صحیح از تعداد گزینه‌ها بیشتر است.']);
        }

        $this->ensureManualPublishNotAllowed('سؤال آزمون', $question?->status, $validated['status']);
        $this->assertBylinesIfPublishing('quiz_questions', $validated['status'], $validated['author_id'], $validated['reviewer_id']);

        $question = DB::transaction(function () use ($question, $validated): QuizQuestion {
            $payload = Arr::except($validated, ['is_free_designated', 'correct_index', 'options']);

            if ($payload['status'] === 'published' && empty($payload['published_at'])) {
                $payload['published_at'] = now();
            }

            if ($question) {
                $this->assertPublicationTransition($question->status, $validated['status']);
                $question->update($payload);
                $question->options()->delete();
            } else {
                $question = QuizQuestion::create($payload);
            }

            foreach ($validated['options'] as $index => $label) {
                QuizOption::create([
                    'quiz_question_id' => $question->id,
                    'label' => $label,
                    'is_correct' => $index === (int) $validated['correct_index'],
                    'sort_order' => $index + 1,
                ]);
            }

            return $question;
        });

        $this->syncFreeDesignation($question, $validated['is_free_designated']);
        $this->finish($session, '✅ سؤال آزمون ذخیره شد.');
    }

    private function submitCourseCover(TelegramChatSession $session, array $message): void
    {
        $course = Course::query()->findOrFail((int) ($session->context['id'] ?? 0));
        $course->cover_image_path = $this->storeTelegramImage($message, 'course-covers', $course->slug ?: 'course');
        $course->save();

        $this->finish($session, '✅ تصویر دوره به‌روزرسانی شد.');
    }

    private function submitBlogCover(TelegramChatSession $session, array $message): void
    {
        $post = BlogPost::query()->findOrFail((int) ($session->context['id'] ?? 0));
        $post->cover_image_path = $this->storeTelegramImage($message, 'blog-covers', $post->slug ?: 'blog');
        $post->save();

        $this->finish($session, '✅ تصویر مطلب وبلاگ به‌روزرسانی شد.');
    }

    private function backupDatabase(int $chatId): bool
    {
        // Queued (Round-6 audit I-4): the dump + gzip + upload can outlive
        // Telegram's webhook patience (~60s), which triggers a retry and a
        // duplicate dump. The database queue is drained every minute by the
        // cron worker (routes/console.php), so the job completes without a
        // daemon on shared hosting.
        \App\Jobs\RunDatabaseBackup::dispatch($chatId);

        return true;
    }

    private function finish(TelegramChatSession $session, string $message): void
    {
        $chatId = (int) $session->telegram_chat_id;
        $session->delete();
        $this->api->sendMessage($chatId, $message, [
            'reply_markup' => $this->menuKeyboard(),
        ]);
    }

    private function currentSession(int $chatId): ?TelegramChatSession
    {
        $session = TelegramChatSession::query()->where('telegram_chat_id', $chatId)->first();

        if (! $session) {
            return null;
        }

        if ($session->expires_at && $session->expires_at->isPast()) {
            $session->delete();

            return null;
        }

        return $session;
    }

    private function storeSession(int $chatId, int $telegramUserId, string $workflow, array $context = []): TelegramChatSession
    {
        return tap(TelegramChatSession::query()->updateOrCreate(
            ['telegram_chat_id' => $chatId],
            [
                'telegram_user_id' => $telegramUserId,
                'workflow' => $workflow,
                'context' => $context,
                'expires_at' => now()->addMinutes(self::SESSION_TTL_MINUTES),
            ],
        ))->refresh();
    }

    private function requiredId(string $argument, string $message): int
    {
        $id = (int) trim($argument);
        if ($id < 1) {
            throw new RuntimeException($message);
        }

        return $id;
    }

    private function parseCommand(string $text): array
    {
        [$raw, $argument] = array_pad(preg_split('/\s+/', trim($text), 2), 2, '');
        $command = ltrim((string) strtok($raw, '@'), '/');

        return [$command, trim($argument)];
    }

    private function isAuthorizedAdmin(array $from): bool
    {
        $telegramUserId = (int) ($from['id'] ?? 0);
        if ($telegramUserId < 1) {
            return false;
        }

        if (TelegramAdmin::query()->where('telegram_user_id', $telegramUserId)->where('is_active', true)->exists()) {
            return true;
        }

        return in_array($telegramUserId, $this->configuredAdminIds(), true);
    }

    private function rememberAdmin(array $from): void
    {
        $telegramUserId = (int) ($from['id'] ?? 0);
        if ($telegramUserId < 1 || ! in_array($telegramUserId, $this->configuredAdminIds(), true)) {
            return;
        }

        TelegramAdmin::query()->updateOrCreate(
            ['telegram_user_id' => $telegramUserId],
            [
                'username' => $from['username'] ?? null,
                'first_name' => $from['first_name'] ?? null,
                'last_name' => $from['last_name'] ?? null,
                'is_active' => true,
            ]
        );
    }

    /** @return int[] */
    private function configuredAdminIds(): array
    {
        $ids = config('services.telegram.admin_ids', []);

        if (is_string($ids)) {
            $ids = array_filter(array_map('trim', explode(',', $ids)));
        }

        return array_values(array_filter(array_map(static fn ($id): int => (int) $id, $ids), static fn (int $id): bool => $id > 0));
    }

    private function showManageMenu(int $chatId, string $entity, int $id): void
    {
        match ($entity) {
            'plan' => $this->showPlanManageMenu($chatId, Plan::query()->findOrFail($id)),
            'blog' => $this->showBlogManageMenu($chatId, BlogPost::query()->findOrFail($id)),
            'subject' => $this->showSubjectManageMenu($chatId, Subject::query()->findOrFail($id)),
            'course' => $this->showCourseManageMenu($chatId, Course::query()->findOrFail($id)),
            'video' => $this->showVideoManageMenu($chatId, Video::query()->findOrFail($id)),
            'note' => $this->showNoteManageMenu($chatId, Note::query()->findOrFail($id)),
            'deck' => $this->showDeckManageMenu($chatId, FlashcardDeck::query()->findOrFail($id)),
            'quiz' => $this->showQuizManageMenu($chatId, Quiz::query()->findOrFail($id)),
            'card' => $this->showCardManageMenu($chatId, Flashcard::query()->findOrFail($id)),
            'question' => $this->showQuestionManageMenu($chatId, QuizQuestion::query()->findOrFail($id)),
            'user' => $this->showUserManageMenu($chatId, $id),
            default => $this->sendWelcome($chatId),
        };
    }

    private function showPlanManageMenu(int $chatId, Plan $plan): void
    {
        $text = implode("\n", [
            '💳 مدیریت پلن #'.$plan->id,
            $plan->name,
            'کد: '.$plan->code,
            'قیمت: '.number_format((int) $plan->price_irr).' ریال',
        ]);

        $this->api->sendMessage($chatId, $text, [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('✏️ ویرایش', 'edit:plan:'.$plan->id), $this->button('🗑 حذف', 'delete:plan:'.$plan->id)],
                [$this->button('↩️ لیست پلن‌ها', 'list:plans')],
            ]),
        ]);
    }

    private function showBlogManageMenu(int $chatId, BlogPost $post): void
    {
        $text = implode("\n", [
            '📝 مدیریت مقاله #'.$post->id,
            $post->title,
            'وضعیت: '.$this->statusLabel($post->status),
            'اسلاگ: '.$post->slug,
        ]);

        $rows = [
            [$this->button('✏️ ویرایش', 'edit:blog:'.$post->id), $this->button('🖼 تصویر شاخص', 'cover:blog:'.$post->id)],
            [$this->button('🗑 حذف', 'delete:blog:'.$post->id), $this->linkButton('🌐 مشاهده', route('blog.show', $post->slug))],
        ];

        foreach ($this->transitionRows('blog', $post->id, $post->status) as $row) {
            $rows[] = $row;
        }

        $rows[] = [$this->button('↩️ لیست مقاله‌ها', 'list:blogs')];

        $this->api->sendMessage($chatId, $text, [
            'reply_markup' => $this->inlineKeyboard($rows),
        ]);
    }

    private function showCourseManageMenu(int $chatId, Course $course): void
    {
        $text = implode("\n", [
            '🎓 مدیریت دوره #'.$course->id,
            $course->title,
            'وضعیت: '.$this->statusLabel($course->status),
            'درس: '.($course->subject?->name ?? '—'),
        ]);

        $rows = [
            [$this->button('✏️ ویرایش', 'edit:course:'.$course->id), $this->button('🖼 کاور', 'cover:course:'.$course->id)],
            [$this->button('🗑 حذف', 'delete:course:'.$course->id), $this->linkButton('🌐 مشاهده', route('courses.show', $course))],
        ];

        foreach ($this->transitionRows('course', $course->id, $course->status) as $row) {
            $rows[] = $row;
        }

        $rows[] = [$this->button('↩️ لیست دوره‌ها', 'list:courses')];

        $this->api->sendMessage($chatId, $text, [
            'reply_markup' => $this->inlineKeyboard($rows),
        ]);
    }

    private function showVideoManageMenu(int $chatId, Video $video): void
    {
        $text = implode("\n", [
            '🎥 مدیریت ویدیو #'.$video->id,
            $video->title,
            'وضعیت: '.$this->statusLabel($video->status),
            'دوره: '.($video->course?->title ?? '—'),
        ]);

        $rows = [
            [$this->button('✏️ ویرایش', 'edit:video:'.$video->id), $this->button('🗑 حذف', 'delete:video:'.$video->id)],
        ];

        foreach ($this->transitionRows('video', $video->id, $video->status) as $row) {
            $rows[] = $row;
        }

        $rows[] = [$this->button('↩️ لیست ویدیوها', 'list:videos')];

        $this->api->sendMessage($chatId, $text, [
            'reply_markup' => $this->inlineKeyboard($rows),
        ]);
    }

    private function showNoteManageMenu(int $chatId, Note $note): void
    {
        $text = implode("\n", [
            '📄 مدیریت جزوه #'.$note->id,
            $note->title,
            'وضعیت: '.$this->statusLabel($note->status),
            'دوره: '.($note->course?->title ?? '—'),
        ]);

        $rows = [
            [$this->button('✏️ ویرایش', 'edit:note:'.$note->id), $this->button('🗑 حذف', 'delete:note:'.$note->id)],
        ];

        foreach ($this->transitionRows('note', $note->id, $note->status) as $row) {
            $rows[] = $row;
        }

        $rows[] = [$this->button('↩️ لیست جزوه‌ها', 'list:notes')];

        $this->api->sendMessage($chatId, $text, [
            'reply_markup' => $this->inlineKeyboard($rows),
        ]);
    }

    private function showDeckManageMenu(int $chatId, FlashcardDeck $deck): void
    {
        $text = implode("\n", [
            '🗂 مدیریت دِک #'.$deck->id,
            $deck->title,
            'وضعیت: '.$this->statusLabel($deck->status),
            'دوره: '.($deck->course?->title ?? '—'),
        ]);

        $rows = [
            [$this->button('✏️ ویرایش', 'edit:deck:'.$deck->id), $this->button('➕ فلش‌کارت', 'new:card:'.$deck->id)],
            [$this->button('🃏 لیست کارت‌ها', 'list:cards:'.$deck->id), $this->button('🗑 حذف', 'delete:deck:'.$deck->id)],
        ];

        foreach ($this->transitionRows('deck', $deck->id, $deck->status) as $row) {
            $rows[] = $row;
        }

        $rows[] = [$this->button('↩️ لیست دِک‌ها', 'list:decks')];

        $this->api->sendMessage($chatId, $text, [
            'reply_markup' => $this->inlineKeyboard($rows),
        ]);
    }

    private function showQuizManageMenu(int $chatId, Quiz $quiz): void
    {
        $text = implode("\n", [
            '🧪 مدیریت آزمون #'.$quiz->id,
            $quiz->title,
            'وضعیت: '.$this->statusLabel($quiz->status),
            'دوره: '.($quiz->course?->title ?? '—'),
        ]);

        $rows = [
            [$this->button('✏️ ویرایش', 'edit:quiz:'.$quiz->id), $this->button('➕ سؤال', 'new:question:'.$quiz->id)],
            [$this->button('❓ لیست سؤال‌ها', 'list:questions:'.$quiz->id), $this->button('🗑 حذف', 'delete:quiz:'.$quiz->id)],
        ];

        foreach ($this->transitionRows('quiz', $quiz->id, $quiz->status) as $row) {
            $rows[] = $row;
        }

        $rows[] = [$this->button('↩️ لیست آزمون‌ها', 'list:quizzes')];

        $this->api->sendMessage($chatId, $text, [
            'reply_markup' => $this->inlineKeyboard($rows),
        ]);
    }

    private function showCardManageMenu(int $chatId, Flashcard $card): void
    {
        $text = implode("\n", [
            '🃏 مدیریت فلش‌کارت #'.$card->id,
            $this->truncate(trim((string) $card->front), 90),
            'وضعیت: '.$this->statusLabel($card->status),
        ]);

        $rows = [
            [$this->button('✏️ ویرایش', 'edit:card:'.$card->id), $this->button('🗑 حذف', 'delete:card:'.$card->id)],
        ];

        foreach ($this->transitionRows('card', $card->id, $card->status) as $row) {
            $rows[] = $row;
        }

        $rows[] = [$this->button('↩️ لیست کارت‌ها', 'list:cards:'.$card->flashcard_deck_id)];

        $this->api->sendMessage($chatId, $text, [
            'reply_markup' => $this->inlineKeyboard($rows),
        ]);
    }

    private function showQuestionManageMenu(int $chatId, QuizQuestion $question): void
    {
        $text = implode("\n", [
            '❓ مدیریت سؤال #'.$question->id,
            $this->truncate(trim((string) $question->prompt), 90),
            'وضعیت: '.$this->statusLabel($question->status),
        ]);

        $rows = [
            [$this->button('✏️ ویرایش', 'edit:question:'.$question->id), $this->button('🗑 حذف', 'delete:question:'.$question->id)],
        ];

        foreach ($this->transitionRows('question', $question->id, $question->status) as $row) {
            $rows[] = $row;
        }

        $rows[] = [$this->button('↩️ لیست سؤال‌ها', 'list:questions:'.$question->quiz_id)];

        $this->api->sendMessage($chatId, $text, [
            'reply_markup' => $this->inlineKeyboard($rows),
        ]);
    }

    private function transitionRows(string $entity, string|int $id, string $status): array
    {
        $rows = [];

        foreach (self::PUBLICATION_TRANSITIONS[$status] ?? [] as $target) {
            $rows[] = [$this->button($this->transitionLabel($target), 'transition:'.$entity.':'.$id.':'.$target)];
        }

        return $rows;
    }

    private function confirmDelete(int $chatId, string $entity, int $id): void
    {
        $this->api->sendMessage($chatId, 'آیا از حذف «'.$this->entityLabel($entity).'» مطمئن هستید؟ این عملیات ممکن است غیرقابل بازگشت باشد.', [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('✅ بله، حذف شود', 'confirmdelete:'.$entity.':'.$id)],
                [$this->button('↩️ بازگشت', 'manage:'.$entity.':'.$id)],
            ]),
        ]);
    }

    private function deleteEntity(int $chatId, string $entity, int $id): void
    {
        $model = $this->managedModel($entity, $id);
        $backCallback = $this->postDeleteCallback($entity, $model);

        // Friendly domain guard mirroring the admin panel: a subject with
        // courses is protected by a RESTRICT foreign key — the SQL error
        // would otherwise surface raw.
        if ($entity === 'subject' && $model instanceof Subject && $model->courses()->exists()) {
            $this->api->sendMessage($chatId, '❌ این شاخه دارای دوره است. ابتدا دوره‌های آن را منتقل یا حذف کنید.', [
                'reply_markup' => $this->inlineKeyboard([
                    [$this->button('↩️ بازگشت', 'manage:subject:'.$id)],
                ]),
            ]);

            return;
        }

        try {
            $model->delete();
        } catch (Throwable $exception) {
            $this->api->sendMessage($chatId, '❌ حذف انجام نشد: '.$this->truncate($exception->getMessage(), 260), [
                'reply_markup' => $this->inlineKeyboard([
                    [$this->button('↩️ بازگشت', 'manage:'.$entity.':'.$id)],
                ]),
            ]);

            return;
        }

        $this->api->sendMessage($chatId, '✅ '.$this->entityLabel($entity).' حذف شد.', [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('↩️ بازگشت به لیست', $backCallback)],
            ]),
        ]);
    }

    private function confirmTransition(int $chatId, string $entity, int $id, string $target): void
    {
        $model = $this->managedModel($entity, $id);
        $this->assertKnownTransitionTarget($target);

        $this->api->sendMessage($chatId, implode("\n", [
            'آیا این تغییر وضعیت را تأیید می‌کنید؟',
            'آیتم: '.$this->entityLabel($entity).' #'.$id,
            'از: '.$this->statusLabel((string) $model->status),
            'به: '.$this->statusLabel($target),
        ]), [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('✅ تأیید', 'confirmtransition:'.$entity.':'.$id.':'.$target)],
                [$this->button('↩️ بازگشت', 'manage:'.$entity.':'.$id)],
            ]),
        ]);
    }

    private function applyTransition(int $chatId, string $entity, int $id, string $target): void
    {
        $model = $this->managedModel($entity, $id);
        $current = (string) $model->status;
        $this->assertKnownTransitionTarget($target);
        $this->assertPublicationTransition($current, $target);

        if ($target === 'published') {
            $this->assertPublishRequirements($entity, $model);
        }

        $model->status = $target;
        if ($target === 'published' && empty($model->published_at)) {
            $model->published_at = now();
        }
        $model->save();

        $this->api->sendMessage($chatId, '✅ وضعیت '.$this->entityLabel($entity).' به «'.$this->statusLabel($target).'» تغییر کرد.', [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('↩️ بازگشت به مدیریت آیتم', 'manage:'.$entity.':'.$id)],
            ]),
        ]);
    }

    private function assertPublishRequirements(string $entity, Model $model): void
    {
        if ($entity === 'blog') {
            $author = trim((string) $model->author_name);
            $reviewer = trim((string) $model->reviewer_name);

            if ($author === '' || $reviewer === '') {
                throw ValidationException::withMessages(['status' => 'انتشار مقاله نیازمند نویسنده و بازبین علمی است.']);
            }

            if (mb_strtolower($author) === mb_strtolower($reviewer)) {
                throw ValidationException::withMessages(['status' => 'نویسنده و بازبین علمی باید دو نام متفاوت باشند.']);
            }

            return;
        }

        if (in_array($entity, ['course', 'video', 'note', 'deck', 'quiz', 'question'], true)) {
            if (empty($model->author_id) || empty($model->reviewer_id)) {
                throw ValidationException::withMessages(['status' => 'انتشار نیازمند نویسنده و بازبین علمی است.']);
            }

            if ((int) $model->author_id === (int) $model->reviewer_id) {
                throw ValidationException::withMessages(['status' => 'نویسنده و بازبین علمی باید دو فرد متفاوت باشند.']);
            }
        }
    }

    private function ensureManualPublishNotAllowed(string $label, ?string $currentStatus, string $targetStatus): void
    {
        if ($targetStatus === 'published' && $currentStatus !== 'published') {
            throw ValidationException::withMessages([
                'status' => 'برای انتشار '.$label.' از دکمه‌های بازبینی/انتشار استفاده کنید.',
            ]);
        }
    }

    private function assertKnownTransitionTarget(string $target): void
    {
        if (! in_array($target, ['draft', 'in_review', 'published', 'archived'], true)) {
            throw new RuntimeException('وضعیت درخواستی نامعتبر است.');
        }
    }

    private function managedModel(string $entity, int $id): Model
    {
        return match ($entity) {
            'plan' => Plan::query()->findOrFail($id),
            'blog' => BlogPost::query()->findOrFail($id),
            'subject' => Subject::query()->findOrFail($id),
            'course' => Course::query()->findOrFail($id),
            'video' => Video::query()->findOrFail($id),
            'note' => Note::query()->findOrFail($id),
            'deck' => FlashcardDeck::query()->findOrFail($id),
            'card' => Flashcard::query()->findOrFail($id),
            'quiz' => Quiz::query()->findOrFail($id),
            'question' => QuizQuestion::query()->findOrFail($id),
            default => throw new RuntimeException('آیتم موردنظر پیدا نشد.'),
        };
    }

    private function indexCallback(string $entity): string
    {
        return match ($entity) {
            'plan' => 'list:plans',
            'blog' => 'list:blogs',
            'subject' => 'list:subjects',
            'course' => 'list:courses',
            'video' => 'list:videos',
            'note' => 'list:notes',
            'deck' => 'list:decks',
            'quiz' => 'list:quizzes',
            'card' => 'list:decks',
            'question' => 'list:quizzes',
            default => 'menu:main',
        };
    }

    private function postDeleteCallback(string $entity, Model $model): string
    {
        return match ($entity) {
            'card' => 'list:cards:'.$model->flashcard_deck_id,
            'question' => 'list:questions:'.$model->quiz_id,
            default => $this->indexCallback($entity),
        };
    }

    private function entityLabel(string $entity): string
    {
        return match ($entity) {
            'plan' => 'پلن',
            'blog' => 'مقاله',
            'subject' => 'شاخهٔ آموزشی',
            'course' => 'دوره',
            'video' => 'ویدیو',
            'note' => 'جزوه',
            'deck' => 'دِک فلش‌کارت',
            'card' => 'فلش‌کارت',
            'quiz' => 'آزمون',
            'question' => 'سؤال',
            default => 'آیتم',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'پیش‌نویس',
            'in_review' => 'در بازبینی',
            'published' => 'منتشر شده',
            'archived' => 'آرشیو',
            default => $status,
        };
    }

    private function transitionLabel(string $status): string
    {
        return match ($status) {
            'draft' => '↩️ بازگشت به پیش‌نویس',
            'in_review' => '🩺 ارسال به بازبینی',
            'published' => '✅ انتشار',
            'archived' => '📦 آرشیو',
            default => $status,
        };
    }

    private function mainMenuMarkup(): string
    {
        return $this->inlineKeyboard([
            [$this->button('💳 پلن‌ها', 'menu:plans'), $this->button('📝 وبلاگ', 'menu:blogs')],
            [$this->button('🎓 دوره‌ها', 'menu:courses'), $this->button('🎥 رسانه', 'menu:media')],
            [$this->button('🧪 آزمون و فلش‌کارت', 'menu:study')],
            [$this->button('👤 کاربران', 'menu:users'), $this->button('🖼 برند و ظاهر', 'menu:brand')],
            [$this->button('📊 آمار سایت', 'stats:run'), $this->button('📜 آخرین فعالیت‌ها', 'activity:run')],
            [$this->button('🗄 بکاپ دیتابیس', 'backup:run')],
        ]);
    }

    private function workflowMarkup(string $backCallback): string
    {
        return $this->inlineKeyboard([
            [$this->button('❌ لغو عملیات', 'cancel:workflow'), $this->button('↩️ بازگشت', $backCallback)],
        ]);
    }

    private function menuKeyboard(): string
    {
        return $this->mainMenuMarkup();
    }

    private function inlineKeyboard(array $rows): string
    {
        return json_encode([
            'inline_keyboard' => $rows,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function button(string $text, string $callbackData): array
    {
        return [
            'text' => $text,
            'callback_data' => $callbackData,
        ];
    }

    private function linkButton(string $text, string $url): array
    {
        return [
            'text' => $text,
            'url' => $url,
        ];
    }

    private function formatValidationErrors(ValidationException $exception): string
    {
        $lines = ['⚠️ داده ارسالی معتبر نیست:'];
        foreach ($exception->errors() as $messages) {
            foreach ((array) $messages as $message) {
                $lines[] = '• '.$message;
            }
        }

        return implode("\n", $lines);
    }

    private function truncate(string $text, int $length): string
    {
        return mb_strlen($text) > $length ? mb_substr($text, 0, $length - 1).'…' : $text;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value === '' || $value === null ? null : (string) $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === '' || $value === null) {
            return null;
        }

        return (int) $value;
    }

    private function nullableBool(mixed $value): ?bool
    {
        if ($value === '' || $value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalized = mb_strtolower(trim((string) $value));

        return match ($normalized) {
            '1', 'true', 'yes', 'on', 'active', 'بله', 'روشن', 'فعال' => true,
            '0', 'false', 'no', 'off', 'inactive', 'خیر', 'خاموش', 'غیرفعال' => false,
            default => throw ValidationException::withMessages(['boolean' => 'مقدار بولی را با yes/no یا 1/0 وارد کنید.']),
        };
    }

    private function nullableDate(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value === '' || $value === null) {
            return null;
        }

        return Carbon::parse((string) $value);
    }

    private function assertPublicationTransition(string $current, string $target): void
    {
        if ($current === $target) {
            return;
        }

        if (! in_array($target, self::PUBLICATION_TRANSITIONS[$current] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "گذار وضعیت از «{$current}» به «{$target}» مجاز نیست.",
            ]);
        }
    }

    private function assertBylinesIfPublishing(string $type, string $status, ?int $authorId, ?int $reviewerId): void
    {
        if ($status !== 'published') {
            return;
        }

        if (! in_array($type, ['courses', 'videos', 'notes', 'flashcard_decks', 'quizzes', 'quiz_questions'], true)) {
            return;
        }

        if (! $authorId || ! $reviewerId) {
            throw ValidationException::withMessages(['status' => 'انتشار نیازمند نویسنده و بازبین است.']);
        }

        if ($authorId === $reviewerId) {
            throw ValidationException::withMessages(['status' => 'نویسنده و بازبین باید متفاوت باشند.']);
        }
    }

    /**
     * playback_url (source_mode: url) is only allowed for hosts on
     * BROCA_EXTERNAL_VIDEO_ORIGINS. Nothing else enforced this at save time —
     * CSP was the only gate, and an empty list meant the stored URL silently
     * failed to play. Fail closed with actionable copy.
     */
    private function assertExternalVideoOrigin(string $url): void
    {
        $host = (string) (parse_url($url, PHP_URL_HOST) ?: '');

        if (! preg_match('/^https?:\/\//i', $url) || $host === '') {
            throw new RuntimeException('playback_url باید یک آدرس http/https معتبر باشد.');
        }

        $allowed = collect(config('broca.external_video_origins', []))
            ->filter(fn ($origin) => is_string($origin) && $origin !== '')
            ->map(fn (string $origin) => (string) (parse_url($origin, PHP_URL_HOST) ?: $origin))
            ->all();

        if ($allowed === []) {
            throw new RuntimeException(
                'پخش از URL بیرونی غیرفعال است. ابتدا BROCA_EXTERNAL_VIDEO_ORIGINS را در فایل .env تنظیم کنید (مثال: https://cdn.example.com).'
            );
        }

        if (! in_array(strtolower($host), array_map('strtolower', $allowed), true)) {
            throw new RuntimeException('دامنهٔ playback_url در فهرست مجاز (BROCA_EXTERNAL_VIDEO_ORIGINS) نیست: '.$host);
        }
    }

    private function syncFreeDesignation(object $item, bool $designated): void
    {
        $current = (bool) $item->is_free_designated;
        if ($current === $designated) {
            return;
        }

        $this->freeItems->set($item, $designated);
    }

    private function storeTelegramVideo(array $message, string $title): string
    {
        $video = $message['video'] ?? $message['document'] ?? null;
        if (! is_array($video) || empty($video['file_id'])) {
            throw new RuntimeException('برای حالت upload باید فایل ویدیو را مستقیم به ربات بفرستید.');
        }

        $extension = pathinfo((string) ($video['file_name'] ?? ''), PATHINFO_EXTENSION);
        if ($extension === '') {
            $extension = str_contains((string) ($video['mime_type'] ?? ''), 'mp4') ? 'mp4' : 'bin';
        }

        $tmp = tempnam(sys_get_temp_dir(), 'broca-video-');
        if ($tmp === false) {
            throw new RuntimeException('ساخت فایل موقت ناموفق بود.');
        }

        $telegramFile = $this->api->getFile((string) $video['file_id']);
        $filePath = (string) ($telegramFile['file_path'] ?? '');
        if ($filePath === '') {
            throw new RuntimeException('مسیر فایل ویدیو از تلگرام دریافت نشد.');
        }

        $this->api->downloadTelegramFile($filePath, $tmp);

        // Videos are paywalled content — they MUST live on the private disk.
        // A copy inside public/ would be statically served by the web server
        // with zero auth (paywall bypass, audit 2026-09-07).
        $filename = Slug::unique(self::fileBase($title).'-'.now()->format('YmdHis'), fn (string $candidate): bool => Storage::disk('local')->exists('videos/'.$candidate.'.'.$extension)).'.'.$extension;
        $storageKey = 'videos/'.$filename;

        Storage::disk('local')->makeDirectory('videos');

        try {
            Storage::disk('local')->put($storageKey, fopen($tmp, 'r'));
        } catch (Throwable $exception) {
            @unlink($tmp);
            throw new RuntimeException('ذخیرهٔ فایل ویدیو روی دیسک ناموفق بود: '.$this->truncate($exception->getMessage(), 160));
        }

        @unlink($tmp);

        // manifest_reference is a bare filename; media() resolves it inside
        // the private videos directory.
        return $filename;
    }

    /**
     * Byte-safe filename base: ext4 caps each path component at 255 BYTES
     * and Persian titles are 2–4 bytes per character, so a long title must
     * be capped by byte length (mb_substr by characters could still exceed
     * the limit and the subsequent file write would fail silently).
     */
    private static function fileBase(string $title, int $maxBytes = 160): string
    {
        $base = trim(Slug::fromTitle($title));

        while ($base !== '' && strlen($base) > $maxBytes) {
            $base = mb_substr($base, 0, (int) max(1, floor(mb_strlen($base) * $maxBytes / strlen($base))));
        }

        return $base !== '' ? $base : 'file';
    }

    /** @return array{storage_key:string,mime_type:string,size_bytes:int,checksum:string} */
    private function storeTelegramDocument(array $message, string $directory, string $title): array
    {
        $document = $message['document'] ?? null;
        if (! is_array($document) || empty($document['file_id'])) {
            throw new RuntimeException('برای این عملیات باید فایل document ارسال کنید.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'broca-doc-');
        if ($tmp === false) {
            throw new RuntimeException('ساخت فایل موقت ناموفق بود.');
        }

        $telegramFile = $this->api->getFile((string) $document['file_id']);
        $filePath = (string) ($telegramFile['file_path'] ?? '');
        if ($filePath === '') {
            throw new RuntimeException('مسیر فایل از تلگرام دریافت نشد.');
        }

        $this->api->downloadTelegramFile($filePath, $tmp);

        return $this->storeLocalFile($tmp, $directory, $title, (string) ($document['mime_type'] ?? 'application/octet-stream'), (string) ($document['file_name'] ?? 'file'));
    }

    /** @return array{storage_key:string,mime_type:string,size_bytes:int,checksum:string} */
    private function storeRemoteDocument(string $url, string $directory, string $title): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'broca-remote-');
        if ($tmp === false) {
            throw new RuntimeException('ساخت فایل موقت ناموفق بود.');
        }

        $hopsLeft = 3;
        $currentUrl = $url;

        try {
            while (true) {
                // The URL ultimately comes from a Telegram message, so treat
                // it as untrusted on EVERY hop: validate scheme + host, then
                // pin the connection to the validated IP. Without pinning, a
                // late DNS swap (rebinding) could still land on loopback
                // after validation; without per-hop re-validation, a public
                // URL could 302 the fetcher to 127.0.0.1 (SSRF) — on shared
                // hosting this box's own services sit there.
                $ip = $this->assertPublicUrl($currentUrl);

                $response = Http::timeout(180)
                    ->withoutRedirecting()
                    ->withOptions(['curl' => [CURLOPT_RESOLVE => [$this->resolvePin($currentUrl, $ip)]]])
                    ->sink($tmp)
                    ->get($currentUrl);

                if ($response->redirect()) {
                    if ($hopsLeft-- <= 0) {
                        @unlink($tmp);

                        throw new RuntimeException('دریافت فایل از این URL مجاز نیست: تعداد ریدایرکت‌ها بیش از حد مجاز است.');
                    }

                    $location = trim((string) $response->header('Location', ''));

                    // Relative Locations are refused outright: resolving them
                    // adds parsing surface for zero benefit in this flow.
                    if ($location === '' || ! preg_match('/^https?:\/\//i', $location)) {
                        @unlink($tmp);

                        throw new RuntimeException('ریدایرکت این URL به مقصد نامعتبر است و دنبال نمی‌شود.');
                    }

                    $currentUrl = $location;

                    continue;
                }

                if (! $response->successful()) {
                    @unlink($tmp);

                    throw new RuntimeException('دریافت فایل از URL ناموفق بود (HTTP '.$response->status().').');
                }

                // Disk-fill guard (audit 2026-09-07): a declared or observed
                // size beyond the cap is rejected instead of quietly filling
                // the (metered, shared) host disk.
                $declared = $response->header('Content-Length');
                if ($declared !== null && is_numeric($declared) && (int) $declared > self::MAX_REMOTE_DOWNLOAD_BYTES) {
                    @unlink($tmp);

                    throw new RuntimeException('حجم فایل بیشتر از سقف مجاز ('.self::MAX_REMOTE_DOWNLOAD_MB.' مگابایت) است.');
                }

                if (filesize($tmp) > self::MAX_REMOTE_DOWNLOAD_BYTES) {
                    @unlink($tmp);

                    throw new RuntimeException('حجم فایل بیشتر از سقف مجاز ('.self::MAX_REMOTE_DOWNLOAD_MB.' مگابایت) است.');
                }

                break;
            }
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            @unlink($tmp);
            throw new RuntimeException('دریافت فایل از URL ناموفق بود: '.$exception->getMessage());
        }

        $mime = (string) $response->header('Content-Type', 'application/octet-stream');
        $name = basename(parse_url($currentUrl, PHP_URL_PATH) ?: 'remote-file');

        return $this->storeLocalFile($tmp, $directory, $title, $mime, $name);
    }

    /**
     * Validate scheme + host of an untrusted URL; returns the resolved
     * public IP so the caller can pin the connection to it.
     */
    private function assertPublicUrl(string $url): string
    {
        if (! preg_match('/^https?:\/\//i', $url)) {
            throw new RuntimeException('فقط URL های http/https پشتیبانی می‌شوند.');
        }

        return $this->assertPublicHost((string) (parse_url($url, PHP_URL_HOST) ?: ''));
    }

    /** host:port:ip entry for CURLOPT_RESOLVE — pins the fetch to the IP we validated. */
    private function resolvePin(string $url, string $ip): string
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT)
            ?? (stripos($url, 'https://') === 0 ? 443 : 80);

        return $host.':'.$port.':'.$ip;
    }

    /**
     * Reject hosts that resolve to non-public IP space and return the
     * resolved IP. The resolved IP is what actually gets connected to, so
     * an unresolvable or private host is blocked the same way. IPv6-only
     * destinations fail closed (gethostbyname is IPv4-only) — acceptable
     * for an admin-only asset-import path.
     */
    private function assertPublicHost(string $host): string
    {
        if ($host === '') {
            throw new RuntimeException('URL معتبر نیست.');
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) !== false
            ? $host
            : gethostbyname($host);

        // gethostbyname returns the input unchanged when DNS fails — the
        // subsequent validation rejects that as a non-IP.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            throw new RuntimeException('ذخیره از این URL مجاز نیست.');
        }

        return $ip;
    }

    /** @return array{storage_key:string,mime_type:string,size_bytes:int,checksum:string} */
    private function storeLocalFile(string $tmpPath, string $directory, string $title, string $mimeType, string $originalName): array
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        if ($extension === '') {
            $extension = match (true) {
                str_contains($mimeType, 'pdf') => 'pdf',
                str_contains($mimeType, 'text/plain') => 'txt',
                default => 'bin',
            };
        }

        $filename = Slug::unique($title.'-'.now()->format('YmdHis'), fn (string $candidate): bool => Storage::disk('local')->exists($directory.'/'.$candidate.'.'.$extension)).'.'.$extension;
        $storageKey = trim($directory, '/').'/'.$filename;

        Storage::disk('local')->put($storageKey, fopen($tmpPath, 'r'));

        $result = [
            'storage_key' => $storageKey,
            'mime_type' => $mimeType,
            'size_bytes' => filesize($tmpPath) ?: 0,
            'checksum' => hash_file('sha256', $tmpPath) ?: '',
        ];

        @unlink($tmpPath);

        return $result;
    }

    private function storeTelegramImage(array $message, string $directory, string $name): string
    {
        $fileId = null;
        $extension = 'jpg';
        $document = $message['document'] ?? null;
        $photos = $message['photo'] ?? null;

        if (is_array($photos) && count($photos) > 0) {
            $photo = end($photos);
            $fileId = is_array($photo) ? ($photo['file_id'] ?? null) : null;
        } elseif (is_array($document) && ! empty($document['file_id']) && str_starts_with((string) ($document['mime_type'] ?? ''), 'image/')) {
            $fileId = $document['file_id'];
            $extension = pathinfo((string) ($document['file_name'] ?? ''), PATHINFO_EXTENSION) ?: 'jpg';
        }

        if (! $fileId) {
            throw new RuntimeException('لطفاً عکس را به‌صورت photo یا document تصویری ارسال کنید.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'broca-image-');
        if ($tmp === false) {
            throw new RuntimeException('ساخت فایل موقت ناموفق بود.');
        }

        $telegramFile = $this->api->getFile((string) $fileId);
        $filePath = (string) ($telegramFile['file_path'] ?? '');
        if ($filePath === '') {
            throw new RuntimeException('مسیر فایل تصویر از تلگرام دریافت نشد.');
        }

        $this->api->downloadTelegramFile($filePath, $tmp);

        $filename = Slug::unique($name.'-cover-'.now()->format('YmdHis'), fn (string $candidate): bool => Storage::disk('public')->exists(trim($directory, '/').'/'.$candidate.'.'.$extension)).'.'.$extension;
        $storagePath = trim($directory, '/').'/'.$filename;
        Storage::disk('public')->put($storagePath, fopen($tmp, 'r'));
        @unlink($tmp);

        return Storage::disk('public')->url($storagePath);
    }

    /* ==================================================================
     |  شاخه‌های آموزشی (Subjects) — ساخت، ویرایش، نمایش/پنهان، حذف
     * ================================================================== */

    private function startSubjectWorkflow(int $chatId, int $telegramUserId, ?int $id): bool
    {
        $subject = $id ? Subject::query()->findOrFail($id) : null;

        $this->storeSession($chatId, $telegramUserId, 'subject.form', ['id' => $subject?->id]);
        $this->api->sendMessage($chatId, $this->subjectTemplate($subject), [
            'reply_markup' => $this->workflowMarkup($subject ? 'manage:subject:'.$subject->id : 'menu:courses'),
        ]);

        return true;
    }

    private function subjectTemplate(?Subject $subject): string
    {
        return implode("\n", [
            'ارسال/ویرایش شاخهٔ آموزشی:',
            'id: '.$this->display($subject?->id),
            'name: '.$this->display($subject?->name),
            'description: '.$this->display($subject?->description),
            'is_visible: '.$this->boolText($subject?->is_visible ?? true),
            'sort_order: '.$this->display($subject?->sort_order ?? 0),
        ]);
    }

    private function submitSubjectForm(TelegramChatSession $session, string $text): void
    {
        $data = StructuredMessageParser::parse($text);
        $id = $this->nullableInt($data['id'] ?? ($session->context['id'] ?? null));
        $subject = $id ? Subject::query()->findOrFail($id) : null;

        $validated = Validator::make([
            'name' => $data['name'] ?? $subject?->name,
            'description' => $this->nullableString($data['description'] ?? $subject?->description),
            'is_visible' => $this->nullableBool($data['is_visible'] ?? (($subject?->is_visible ?? true) ? '1' : '0')),
            'sort_order' => $this->nullableInt($data['sort_order'] ?? $subject?->sort_order ?? 0),
        ], [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_visible' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ])->validate();

        if ($subject) {
            $nameChanged = $validated['name'] !== $subject->name;
            $subject->fill(collect($validated)->except(['sort_order'])->all());
            $subject->sort_order = (int) ($validated['sort_order'] ?? $subject->sort_order);

            // Renaming re-slugs, exactly like the admin panel.
            if ($nameChanged) {
                $subject->slug = Slug::unique($validated['name'], fn (string $slug): bool => Subject::query()
                    ->where('slug', $slug)
                    ->where('id', '!=', $subject->id)
                    ->exists());
            }

            $subject->save();
        } else {
            $subject = new Subject(collect($validated)->except(['sort_order'])->all());
            $subject->sort_order = (int) ($validated['sort_order'] ?? 0);
            $subject->slug = Slug::unique($validated['name'], fn (string $slug): bool => Subject::query()->where('slug', $slug)->exists());
            $subject->save();
        }

        $this->finish($session, '✅ شاخه ذخیره شد: #'.$subject->id.' — '.$subject->name);
    }

    private function showSubjectManageMenu(int $chatId, Subject $subject): void
    {
        $this->api->sendMessage($chatId, implode("\n", [
            '📚 مدیریت شاخه #'.$subject->id,
            $subject->name,
            'دوره‌ها: '.$subject->courses()->count(),
            'نمایش: '.($subject->is_visible ? '👁 نمایان' : '🙈 پنهان'),
        ]), [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('✏️ ویرایش', 'edit:subject:'.$subject->id), $this->button('🗑 حذف', 'delete:subject:'.$subject->id)],
                [$this->button($subject->is_visible ? '🙈 پنهان کردن' : '👁 نمایان کردن', 'subjectvisibility:'.$subject->id)],
                [$this->button('↩️ لیست شاخه‌ها', 'list:subjects')],
            ]),
        ]);
    }

    private function confirmSubjectVisibility(int $chatId, int $id): void
    {
        $subject = Subject::query()->findOrFail($id);
        $target = ! $subject->is_visible;

        $this->api->sendMessage($chatId, implode("\n", [
            'تغییر نمایش شاخه را تأیید می‌کنید؟',
            'شاخه: '.$subject->name,
            'از: '.($subject->is_visible ? 'نمایان' : 'پنهان'),
            'به: '.($target ? 'نمایان' : 'پنهان'),
            $target ? '' : '⚠️ شاخهٔ پنهان از کاتالوگ، نقشهٔ سایت و نسخهٔ Markdown حذف می‌شود.',
        ]), [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('✅ تأیید', 'confirmsubjectvisibility:'.$subject->id)],
                [$this->button('↩️ بازگشت', 'manage:subject:'.$subject->id)],
            ]),
        ]);
    }

    private function applySubjectVisibility(int $chatId, int $id): void
    {
        $subject = Subject::query()->findOrFail($id);
        $subject->is_visible = ! $subject->is_visible;
        $subject->save();

        $this->api->sendMessage($chatId, '✅ نمایش شاخه به «'.($subject->is_visible ? 'نمایان' : 'پنهان').'» تغییر کرد.', [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('↩️ بازگشت به مدیریت شاخه', 'manage:subject:'.$subject->id)],
            ]),
        ]);
    }

    /* ==================================================================
     |  مدیریت کاربران — لیست، کارت، تعلیق/فعال‌سازی، مدیر، ثبت‌نام
     * ================================================================== */

    private function userStatusLabel(string $status): string
    {
        return match ($status) {
            'active' => '✅ فعال',
            'suspended' => '⛔️ معلق',
            default => $status,
        };
    }

    private function listUsers(int $chatId, int $page): bool
    {
        $perPage = 8;
        $query = User::query()->latest('id');
        $total = (clone $query)->count();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $users = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $lines = ['👤 کاربران — '.$total.' نفر | صفحهٔ '.$page.' از '.$pages.':'];
        $buttons = [];

        foreach ($users as $user) {
            $lines[] = sprintf(
                '#%d | %s | %s | %s%s',
                $user->id,
                $this->truncate($user->name, 24),
                $user->phone !== null && $user->phone !== '' ? $user->phone : $user->email,
                $this->userStatusLabel((string) $user->status),
                $user->is_admin ? ' | 🛡 مدیر' : ''
            );
            $buttons[] = [$this->button('🛠 #'.$user->id.' — '.$this->truncate($user->name, 24), 'manage:user:'.$user->id)];
        }

        if ($users->isEmpty()) {
            $lines[] = 'کاربری یافت نشد.';
        }

        $nav = [];
        if ($page > 1) {
            $nav[] = $this->button('▶️ صفحهٔ قبلی', 'list:users:'.($page - 1));
        }
        if ($page < $pages) {
            $nav[] = $this->button('صفحهٔ بعدی ◀️', 'list:users:'.($page + 1));
        }
        if ($nav !== []) {
            $buttons[] = $nav;
        }
        $buttons[] = [$this->button('↩️ منوی کاربران', 'menu:users')];

        $this->api->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => $this->inlineKeyboard($buttons),
        ]);

        return true;
    }

    private function showUserManageMenu(int $chatId, int $id): bool
    {
        $user = User::query()->withCount(['enrollments', 'subscriptions', 'quizAttempts'])->findOrFail($id);
        $subscription = $user->activeSubscription();
        $timezone = config('broca.display_timezone');

        $lines = [
            '👤 کاربر #'.$user->id,
            'نام: '.$user->name,
            'ایمیل: '.$user->email,
            'موبایل: '.($user->phone !== null && $user->phone !== '' ? $user->phone : '—'),
            'وضعیت: '.$this->userStatusLabel((string) $user->status),
            'نقش: '.($user->is_admin ? '🛡 مدیر' : 'دانشجو'),
            'اشتراک: '.($subscription
                ? ($subscription->plan?->name ?? 'پلن').($subscription->ends_at ? ' — تا '.$subscription->ends_at->timezone($timezone)->format('Y/m/d') : ' — بدون انقضا')
                : 'بدون اشتراک فعال'),
            'ثبت‌نام در دوره‌ها: '.$user->enrollments_count.' | آزمون: '.$user->quiz_attempts_count.' | اشتراک‌ها: '.$user->subscriptions_count,
            'عضویت: '.$user->created_at?->timezone($timezone)->format('Y/m/d'),
        ];

        $this->api->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button($user->status === 'active' ? '⛔️ تعلیق حساب' : '✅ فعال‌سازی حساب', 'userstatus:'.$user->id)],
                [$this->button($user->is_admin ? '🛡 سلب مدیریت' : '🛡 ارتقا به مدیر', 'useradmin:'.$user->id)],
                [$this->button('🎓 ثبت‌نام در دوره', 'userenroll:'.$user->id), $this->button('📚 لغو ثبت‌نام', 'userunenroll:'.$user->id)],
                [$this->button('↩️ لیست کاربران', 'list:users:1')],
            ]),
        ]);

        return true;
    }

    private function confirmUserStatus(int $chatId, int $id): void
    {
        $user = User::query()->findOrFail($id);
        $suspending = $user->status === 'active';

        $this->api->sendMessage($chatId, implode("\n", [
            'تغییر وضعیت حساب را تأیید می‌کنید؟',
            'کاربر: #'.$user->id.' — '.$user->name,
            'از: '.$this->userStatusLabel((string) $user->status),
            'به: '.($suspending ? '⛔️ معلق' : '✅ فعال'),
            $suspending ? '⚠️ کاربر معلق فوراً از همهٔ نشست‌های فعال خارج می‌شود.' : '',
        ]), [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('✅ تأیید', 'confirmuserstatus:'.$user->id)],
                [$this->button('↩️ بازگشت', 'manage:user:'.$user->id)],
            ]),
        ]);
    }

    private function applyUserStatus(int $chatId, int $id): void
    {
        $user = User::query()->findOrFail($id);
        $user->status = $user->status === 'active' ? 'suspended' : 'active';
        $user->save();

        $this->api->sendMessage($chatId, '✅ وضعیت کاربر #'.$user->id.' به '.$this->userStatusLabel((string) $user->status).' تغییر کرد.', [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('↩️ بازگشت به کارت کاربر', 'manage:user:'.$user->id)],
            ]),
        ]);
    }

    private function confirmUserAdmin(int $chatId, int $id): void
    {
        $user = User::query()->findOrFail($id);
        $promoting = ! $user->is_admin;

        $this->api->sendMessage($chatId, implode("\n", [
            'تغییر نقش کاربر را تأیید می‌کنید؟',
            'کاربر: #'.$user->id.' — '.$user->name,
            'از: '.($user->is_admin ? '🛡 مدیر' : 'دانشجو'),
            'به: '.($promoting ? '🛡 مدیر (دسترسی کامل پنل)' : 'دانشجو'),
            $promoting ? '⚠️ مدیران به همهٔ محتوا، کاربران و آمار دسترسی دارند.' : '⚠️ در صورت سلب مدیریتِ آخرین مدیر، عملیات رد می‌شود.',
        ]), [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('✅ تأیید', 'confirmuseradmin:'.$user->id)],
                [$this->button('↩️ بازگشت', 'manage:user:'.$user->id)],
            ]),
        ]);
    }

    private function applyUserAdmin(int $chatId, int $id): void
    {
        // Same invariant as the admin panel, now under a row lock: two
        // concurrent demotions can never remove the final administrator.
        $result = DB::transaction(function () use ($id): array {
            $target = User::query()->whereKey($id)->lockForUpdate()->first();

            if ($target === null) {
                return [null, 'کاربر یافت نشد.'];
            }

            if ($target->is_admin && User::query()->where('is_admin', true)->count() <= 1) {
                return [null, 'حداقل یک مدیر باید در سیستم باقی بماند؛ سلب مدیریت این حساب ممکن نیست.'];
            }

            $target->forceFill(['is_admin' => ! $target->is_admin])->save();

            return [$target, null];
        });

        [$target, $error] = $result;

        if ($error !== null) {
            $this->api->sendMessage($chatId, '❌ '.$error, [
                'reply_markup' => $this->inlineKeyboard([
                    [$this->button('↩️ بازگشت', 'manage:user:'.$id)],
                ]),
            ]);

            return;
        }

        $this->api->sendMessage($chatId, '✅ نقش کاربر #'.$target->id.' به '.($target->is_admin ? '🛡 مدیر' : 'دانشجو').' تغییر کرد.', [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('↩️ بازگشت به کارت کاربر', 'manage:user:'.$target->id)],
            ]),
        ]);
    }

    private function pickCourseForEnrollment(int $chatId, int $userId): void
    {
        $user = User::query()->findOrFail($userId);
        $courses = Course::query()->with('subject')->published()->orderBy('sort_order')->orderBy('id')->limit(10)->get();

        if ($courses->isEmpty()) {
            $this->api->sendMessage($chatId, 'هنوز دورهٔ منتشرشده‌ای برای ثبت‌نام وجود ندارد.', [
                'reply_markup' => $this->inlineKeyboard([
                    [$this->button('↩️ بازگشت', 'manage:user:'.$userId)],
                ]),
            ]);

            return;
        }

        $lines = ['🎓 ثبت‌نام '.$user->name.' در کدام دوره؟ (۱۰ دورهٔ منتشرشدهٔ آخر):'];
        $buttons = [];

        foreach ($courses as $course) {
            $enrolled = $user->enrollments()->where('course_id', $course->id)->where('status', 'active')->exists();
            $lines[] = sprintf('#%d | %s%s', $course->id, $this->truncate($course->title, 40), $enrolled ? ' (قبلاً ثبت‌نام شده)' : '');
            if (! $enrolled) {
                $buttons[] = [$this->button('➕ #'.$course->id.' — '.$this->truncate($course->title, 26), 'confirmenroll:'.$userId.':'.$course->id)];
            }
        }

        $buttons[] = [$this->button('↩️ بازگشت', 'manage:user:'.$userId)];

        $this->api->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => $this->inlineKeyboard($buttons),
        ]);
    }

    private function applyEnrollment(int $chatId, int $userId, int $courseId): void
    {
        $user = User::query()->findOrFail($userId);
        $course = Course::query()->published()->findOrFail($courseId);

        $enrollment = $user->enrollments()->firstOrCreate(
            ['course_id' => $course->id],
            ['enrolled_at' => now(), 'status' => 'active'],
        );

        $message = $enrollment->wasRecentlyCreated
            ? '✅ کاربر در دورهٔ «'.$course->title.'» ثبت‌نام شد.'
            : 'ℹ️ این کاربر از قبل در این دوره ثبت‌نام داشته است.';

        $this->api->sendMessage($chatId, $message, [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('↩️ بازگشت به کارت کاربر', 'manage:user:'.$userId)],
            ]),
        ]);
    }

    private function pickEnrollmentForRemoval(int $chatId, int $userId): void
    {
        $user = User::query()->findOrFail($userId);
        $enrollments = $user->enrollments()->with('course')->where('status', 'active')->get();

        if ($enrollments->isEmpty()) {
            $this->api->sendMessage($chatId, 'این کاربر ثبت‌نام فعالی ندارد.', [
                'reply_markup' => $this->inlineKeyboard([
                    [$this->button('↩️ بازگشت', 'manage:user:'.$userId)],
                ]),
            ]);

            return;
        }

        $lines = ['📚 لغو ثبت‌نام '.$user->name.' از کدام دوره؟'];
        $buttons = [];

        foreach ($enrollments as $enrollment) {
            $lines[] = sprintf('#%d | %s', $enrollment->course_id, $enrollment->course?->title ?? 'دورهٔ حذف‌شده');
            $buttons[] = [$this->button('➖ #'.$enrollment->course_id.' — '.$this->truncate((string) $enrollment->course?->title, 24), 'confirmunenroll:'.$userId.':'.$enrollment->course_id)];
        }

        $buttons[] = [$this->button('↩️ بازگشت', 'manage:user:'.$userId)];

        $this->api->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => $this->inlineKeyboard($buttons),
        ]);
    }

    private function applyUnenrollment(int $chatId, int $userId, int $courseId): void
    {
        $user = User::query()->findOrFail($userId);
        $enrollment = $user->enrollments()->where('course_id', $courseId)->where('status', 'active')->first();

        if ($enrollment === null) {
            $this->api->sendMessage($chatId, 'ثبت‌نام فعالی برای این دوره پیدا نشد.', [
                'reply_markup' => $this->inlineKeyboard([
                    [$this->button('↩️ بازگشت', 'manage:user:'.$userId)],
                ]),
            ]);

            return;
        }

        // Cancelled — not deleted — so the enrollment history stays auditable.
        $enrollment->update(['status' => 'cancelled']);

        $this->api->sendMessage($chatId, '✅ ثبت‌نام کاربر در دورهٔ «'.($enrollment->course?->title ?? '#'.$courseId).'» لغو شد.', [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('↩️ بازگشت به کارت کاربر', 'manage:user:'.$userId)],
            ]),
        ]);
    }

    /* ==================================================================
     |  آمار سایت و آخرین فعالیت‌ها
     * ================================================================== */

    private function sendStats(int $chatId): bool
    {
        $stats = [
            '👥 کاربران' => User::count(),
            '🆕 ثبت‌نام امروز' => User::query()->where('created_at', '>=', now()->startOfDay())->count(),
            '🎓 دوره‌های منتشرشده' => Course::published()->count(),
            '🎥 ویدیوهای منتشرشده' => Video::published()->count(),
            '📄 جزوات منتشرشده' => Note::published()->count(),
            '🗂 فلش‌کارت‌های منتشرشده' => Flashcard::published()->count(),
            '🧪 آزمون‌های منتشرشده' => Quiz::published()->count(),
            '❓ سؤال‌های منتشرشده' => QuizQuestion::published()->count(),
            '📝 مقالات منتشرشده' => BlogPost::published()->count(),
            '⭐️ اشتراک‌های فعال' => Subscription::query()->where('status', 'active')->count(),
            '🧾 درآمد ۳۰ روز گذشته (ریال)' => number_format((int) Invoice::query()->where('status', Invoice::STATUS_PAID)->where('paid_at', '>=', now()->subDays(30))->sum('amount_irr')),
            '⏳ فاکتورهای در انتظار پرداخت' => Invoice::query()->where('status', Invoice::STATUS_PENDING)->count(),
        ];

        $lines = ['📊 آمار بروکا:', ''];
        foreach ($stats as $label => $value) {
            $lines[] = $label.': '.$value;
        }

        $this->api->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('📜 آخرین فعالیت‌ها', 'activity:run')],
                [$this->button('↩️ منوی اصلی', 'menu:main')],
            ]),
        ]);

        return true;
    }

    private function sendActivityLog(int $chatId): bool
    {
        $logs = AdminActivityLog::query()->with('user')->latest('id')->limit(10)->get();
        $timezone = config('broca.display_timezone');

        if ($logs->isEmpty()) {
            $this->api->sendMessage($chatId, 'هنوز فعالیتی ثبت نشده است.', [
                'reply_markup' => $this->inlineKeyboard([
                    [$this->button('↩️ منوی اصلی', 'menu:main')],
                ]),
            ]);

            return true;
        }

        $lines = ['📜 ۱۰ فعالیت اخیر مدیران:'];

        foreach ($logs as $log) {
            $lines[] = implode(' | ', array_filter([
                '#'.$log->id,
                $log->action,
                $log->actor_email_snapshot ?: $log->user?->email,
                'HTTP '.$log->status_code,
                $log->created_at?->timezone($timezone)->format('m/d H:i'),
            ]));
        }

        $this->api->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('↩️ منوی اصلی', 'menu:main')],
            ]),
        ]);

        return true;
    }

    /* ==================================================================
     |  برند و ظاهر — لوگو و تصویر هیرو
     * ================================================================== */

    private function startBrandImageWorkflow(int $chatId, int $telegramUserId, string $target): bool
    {
        $this->storeSession($chatId, $telegramUserId, $target === 'hero' ? 'brand.hero' : 'brand.logo', ['target' => $target]);

        $text = $target === 'hero'
            ? implode("\n", [
                '🌄 تصویر هیرو صفحهٔ اصلی را بفرستید (photo یا document تصویری).',
                '',
                'نکته‌ها:',
                '• نسبت پیشنهادی ۳:۴ عمودی؛ حداکثر ۸ مگابایت.',
                '• نسخهٔ WebP به‌صورت خودکار بازسازی می‌شود؛ اگر سرور GD نداشته باشد، سایت به JPEG برمی‌گردد.',
            ])
            : implode("\n", [
                '🖼 لوگوی سایت را بفرستید (photo یا document تصویری).',
                '',
                'نکته‌ها:',
                '• PNG با پس‌زمینهٔ شفاف بهترین نتیجه را می‌دهد.',
                '• لوگو در هدر و فوتر جایگزین حرف «ب» می‌شود.',
                '• فرمت SVG پذیرفته نمی‌شود (SVG می‌تواند کد اجرایی حمل کند).',
            ]);

        $this->api->sendMessage($chatId, $text, [
            'reply_markup' => $this->workflowMarkup('menu:brand'),
        ]);

        return true;
    }

    private function submitBrandImage(TelegramChatSession $session, array $message): void
    {
        $target = (string) $session->workflow === 'brand.hero' ? 'hero' : 'logo';
        $image = $this->extractImageUpload($message);

        $url = BrandAssets::store($image, $target);

        $this->finish($session, $target === 'hero'
            ? "✅ تصویر هیرو به‌روزرسانی شد.\nآدرس فایل: ".$url."\nتغییر بلافاصله در صفحهٔ اصلی دیده می‌شود."
            : "✅ لوگوی سایت به‌روزرسانی شد.\nآدرس فایل: ".$url."\nلوگوی جدید در هدر و فوتر جایگزین حرف «ب» شده است.");
    }

    private function confirmRemoveBrand(int $chatId, string $target): bool
    {
        if ($target !== 'logo') {
            $this->api->sendMessage($chatId, 'حذف تصویر هیرو از طریق ربات انجام نمی‌شود — صفحهٔ اصلی همیشه به یک تصویر نیاز دارد. برای تغییر، همان دکمهٔ «تغییر هیرو» را بزنید.', [
                'reply_markup' => $this->inlineKeyboard([
                    [$this->button('↩️ بازگشت', 'menu:brand')],
                ]),
            ]);

            return true;
        }

        $this->api->sendMessage($chatId, 'لوگو حذف شود؟ سایت دوباره حرف پیش‌فرض «ب» را نشان می‌دهد.', [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('✅ بله، حذف شود', 'confirmremovebrand:logo')],
                [$this->button('↩️ بازگشت', 'menu:brand')],
            ]),
        ]);

        return true;
    }

    private function applyRemoveBrand(int $chatId, string $target): void
    {
        if ($target !== 'logo') {
            $this->api->sendMessage($chatId, 'حذف هیرو پشتیبانی نمی‌شود.', [
                'reply_markup' => $this->inlineKeyboard([
                    [$this->button('↩️ بازگشت', 'menu:brand')],
                ]),
            ]);

            return;
        }

        BrandAssets::removeLogo();

        $this->api->sendMessage($chatId, '✅ لوگو حذف شد؛ سایت دوباره حرف «ب» را نشان می‌دهد.', [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('↩️ منوی برند', 'menu:brand')],
            ]),
        ]);
    }

    /**
     * Extract an image upload (photo or image document) into raw bytes +
     * normalized extension. SVG is refused outright: it is an operator-only
     * path, but an SVG logo is same-origin scriptable — an unnecessary
     * stored-XSS vector.
     *
     * @return array{body:string, extension:string}
     */
    private function extractImageUpload(array $message): array
    {
        $photo = $message['photo'] ?? null;
        $document = $message['document'] ?? null;
        $fileId = null;
        $extension = 'jpg';

        if (is_array($photo) && count($photo) > 0) {
            $largest = end($photo);
            $fileId = is_array($largest) ? ($largest['file_id'] ?? null) : null;
            $extension = 'jpg';
        } elseif (is_array($document) && ! empty($document['file_id']) && str_starts_with((string) ($document['mime_type'] ?? ''), 'image/')) {
            $mime = strtolower((string) ($document['mime_type'] ?? ''));

            if (str_contains($mime, 'svg')) {
                throw new RuntimeException('فرمت SVG پذیرفته نمی‌شود؛ PNG یا JPG بفرستید.');
            }

            $fileId = (string) $document['file_id'];
            $extension = match (true) {
                str_contains($mime, 'png') => 'png',
                str_contains($mime, 'webp') => 'webp',
                str_contains($mime, 'gif') => 'gif',
                default => 'jpg',
            };
        }

        if (! $fileId) {
            throw new RuntimeException('لطفاً تصویر را به‌صورت photo یا document تصویری ارسال کنید.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'broca-brand-');
        if ($tmp === false) {
            throw new RuntimeException('ساخت فایل موقت ناموفق بود.');
        }

        $telegramFile = $this->api->getFile((string) $fileId);
        $filePath = (string) ($telegramFile['file_path'] ?? '');
        if ($filePath === '') {
            @unlink($tmp);
            throw new RuntimeException('مسیر فایل تصویر از تلگرام دریافت نشد.');
        }

        $this->api->downloadTelegramFile($filePath, $tmp);

        $bytes = (string) file_get_contents($tmp);
        @unlink($tmp);

        if ($bytes === '') {
            throw new RuntimeException('دانلود تصویر ناموفق بود؛ دوباره تلاش کنید.');
        }

        if (strlen($bytes) > 8 * 1024 * 1024) {
            throw new RuntimeException('حجم تصویر باید کمتر از ۸ مگابایت باشد.');
        }

        return ['body' => $bytes, 'extension' => $extension];
    }

    private function planTemplate(?Plan $plan): string
    {
        return implode("\n", [
            'ارسال/ویرایش پلن:',
            'id: '.$this->display($plan?->id),
            'code: '.$this->display($plan?->code),
            'name: '.$this->display($plan?->name),
            'description: '.$this->display($plan?->description),
            'price_irr: '.$this->display($plan?->price_irr),
            'duration_months: '.$this->display($plan?->duration_months),
            'is_active: '.$this->boolText($plan?->is_active),
            'sort_order: '.$this->display($plan?->sort_order),
        ]);
    }

    private function blogTemplate(?BlogPost $post): string
    {
        return implode("\n", [
            'ارسال/ویرایش مطلب وبلاگ:',
            'id: '.$this->display($post?->id),
            'title: '.$this->display($post?->title),
            'category: '.$this->display($post?->category),
            'author_name: '.$this->display($post?->author_name),
            'reviewer_name: '.$this->display($post?->reviewer_name),
            'excerpt: '.$this->display($post?->excerpt),
            'status: '.$this->display($post?->status),
            'published_at: '.$this->dateText($post?->published_at),
            'meta_title: '.$this->display($post?->meta_title),
            'meta_description: '.$this->display($post?->meta_description),
            'cover_image_path: '.$this->display($post?->cover_image_path),
            '[content]',
            $this->display($post?->content),
            '[/content]',
        ]);
    }

    private function courseTemplate(?Course $course): string
    {
        return implode("\n", [
            'ارسال/ویرایش دوره:',
            'id: '.$this->display($course?->id),
            'subject_id: '.$this->display($course?->subject_id),
            'title: '.$this->display($course?->title),
            'excerpt: '.$this->display($course?->excerpt),
            'level: '.$this->display($course?->level),
            'author_id: '.$this->display($course?->author_id),
            'reviewer_id: '.$this->display($course?->reviewer_id),
            'sort_order: '.$this->display($course?->sort_order),
            'status: '.$this->display($course?->status),
            'published_at: '.$this->dateText($course?->published_at),
            'cover_image_path: '.$this->display($course?->cover_image_path),
            '[description]',
            $this->display($course?->description),
            '[/description]',
        ]);
    }

    private function videoTemplate(?Video $video): string
    {
        return implode("\n", [
            'ارسال/ویرایش ویدیو:',
            'id: '.$this->display($video?->id),
            'course_id: '.$this->display($video?->course_id),
            'title: '.$this->display($video?->title),
            'description: '.$this->display($video?->description),
            'duration_seconds: '.$this->display($video?->duration_seconds),
            'completion_threshold_percent: '.$this->display($video?->completion_threshold_percent),
            'author_id: '.$this->display($video?->author_id),
            'reviewer_id: '.$this->display($video?->reviewer_id),
            'sort_order: '.$this->display($video?->sort_order),
            'status: '.$this->display($video?->status),
            'published_at: '.$this->dateText($video?->published_at),
            'is_free_designated: '.$this->boolText($video?->is_free_designated),
            'source_mode: '.($video ? 'keep' : 'upload'),
            'playback_url: '.$this->display($video?->playback_asset_id),
        ]);
    }

    private function noteTemplate(?Note $note): string
    {
        return implode("\n", [
            'ارسال/ویرایش جزوه:',
            'id: '.$this->display($note?->id),
            'course_id: '.$this->display($note?->course_id),
            'title: '.$this->display($note?->title),
            'description: '.$this->display($note?->description),
            'author_id: '.$this->display($note?->author_id),
            'reviewer_id: '.$this->display($note?->reviewer_id),
            'sort_order: '.$this->display($note?->sort_order),
            'status: '.$this->display($note?->status),
            'published_at: '.$this->dateText($note?->published_at),
            'is_free_designated: '.$this->boolText($note?->is_free_designated),
            'source_mode: '.($note ? 'keep' : 'upload'),
            'file_url: ',
        ]);
    }

    private function deckTemplate(?FlashcardDeck $deck): string
    {
        return implode("\n", [
            'ارسال/ویرایش دِک فلش‌کارت:',
            'id: '.$this->display($deck?->id),
            'course_id: '.$this->display($deck?->course_id),
            'title: '.$this->display($deck?->title),
            'description: '.$this->display($deck?->description),
            'author_id: '.$this->display($deck?->author_id),
            'reviewer_id: '.$this->display($deck?->reviewer_id),
            'sort_order: '.$this->display($deck?->sort_order),
            'status: '.$this->display($deck?->status),
            'published_at: '.$this->dateText($deck?->published_at),
        ]);
    }

    private function cardTemplate(?Flashcard $card, ?int $deckId): string
    {
        return implode("\n", [
            'ارسال/ویرایش فلش‌کارت:',
            'id: '.$this->display($card?->id),
            'flashcard_deck_id: '.$this->display($card?->flashcard_deck_id ?: $deckId),
            'hint: '.$this->display($card?->hint),
            'sort_order: '.$this->display($card?->sort_order),
            'status: '.$this->display($card?->status),
            'published_at: '.$this->dateText($card?->published_at),
            'is_free_designated: '.$this->boolText($card?->is_free_designated),
            '[front]',
            $this->display($card?->front),
            '[/front]',
            '[back]',
            $this->display($card?->back),
            '[/back]',
        ]);
    }

    private function quizTemplate(?Quiz $quiz): string
    {
        return implode("\n", [
            'ارسال/ویرایش آزمون:',
            'id: '.$this->display($quiz?->id),
            'course_id: '.$this->display($quiz?->course_id),
            'title: '.$this->display($quiz?->title),
            'description: '.$this->display($quiz?->description),
            'pass_threshold_percent: '.$this->display($quiz?->pass_threshold_percent),
            'author_id: '.$this->display($quiz?->author_id),
            'reviewer_id: '.$this->display($quiz?->reviewer_id),
            'sort_order: '.$this->display($quiz?->sort_order),
            'status: '.$this->display($quiz?->status),
            'published_at: '.$this->dateText($quiz?->published_at),
        ]);
    }

    private function questionTemplate(?QuizQuestion $question, ?int $quizId): string
    {
        $options = $question
            ? $question->options()->orderBy('sort_order')->pluck('label')->implode(' | ')
            : '';
        $searchResult = $question
            ? $question->options()->orderBy('sort_order')->pluck('is_correct')->search(fn ($value) => (bool) $value === true)
            : null;
        $correctIndex = $searchResult === false ? '' : (string) $searchResult;

        return implode("\n", [
            'ارسال/ویرایش سؤال آزمون:',
            'id: '.$this->display($question?->id),
            'quiz_id: '.$this->display($question?->quiz_id ?: $quizId),
            'source_citation: '.$this->display($question?->source_citation),
            'author_id: '.$this->display($question?->author_id),
            'reviewer_id: '.$this->display($question?->reviewer_id),
            'sort_order: '.$this->display($question?->sort_order),
            'status: '.$this->display($question?->status),
            'published_at: '.$this->dateText($question?->published_at),
            'is_free_designated: '.$this->boolText($question?->is_free_designated),
            'correct_index: '.$correctIndex,
            'options: '.$options,
            '[prompt]',
            $this->display($question?->prompt),
            '[/prompt]',
            '[explanation]',
            $this->display($question?->explanation),
            '[/explanation]',
        ]);
    }

    private function boolText(?bool $value): string
    {
        return $value ? 'yes' : 'no';
    }

    private function dateText(?Carbon $value): string
    {
        return $value?->toDateTimeString() ?? '';
    }

    private function display(mixed $value): string
    {
        return $value === null ? '' : (string) $value;
    }

    private function resolveOptionalPlan(TelegramChatSession $session, array $data): ?Plan
    {
        return $this->resolveOptionalModel($session, $data, Plan::class);
    }

    private function resolveOptionalBlogPost(TelegramChatSession $session, array $data): ?BlogPost
    {
        return $this->resolveOptionalModel($session, $data, BlogPost::class);
    }

    private function resolveOptionalCourse(TelegramChatSession $session, array $data): ?Course
    {
        return $this->resolveOptionalModel($session, $data, Course::class);
    }

    private function resolveOptionalVideo(TelegramChatSession $session, array $data): ?Video
    {
        return $this->resolveOptionalModel($session, $data, Video::class);
    }

    private function resolveOptionalNote(TelegramChatSession $session, array $data): ?Note
    {
        return $this->resolveOptionalModel($session, $data, Note::class);
    }

    private function resolveOptionalDeck(TelegramChatSession $session, array $data): ?FlashcardDeck
    {
        return $this->resolveOptionalModel($session, $data, FlashcardDeck::class);
    }

    private function resolveOptionalCard(TelegramChatSession $session, array $data): ?Flashcard
    {
        return $this->resolveOptionalModel($session, $data, Flashcard::class);
    }

    private function resolveOptionalQuiz(TelegramChatSession $session, array $data): ?Quiz
    {
        return $this->resolveOptionalModel($session, $data, Quiz::class);
    }

    private function resolveOptionalQuestion(TelegramChatSession $session, array $data): ?QuizQuestion
    {
        return $this->resolveOptionalModel($session, $data, QuizQuestion::class);
    }

    /** @template TModel of \Illuminate\Database\Eloquent\Model
     *  @param class-string<TModel> $modelClass
     *  @return TModel|null
     */
    private function resolveOptionalModel(TelegramChatSession $session, array $data, string $modelClass)
    {
        $id = $this->nullableInt($data['id'] ?? ($session->context['id'] ?? null));

        return $id ? $modelClass::query()->findOrFail($id) : null;
    }
}
