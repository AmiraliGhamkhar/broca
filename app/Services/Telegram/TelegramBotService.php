<?php

namespace App\Services\Telegram;

use App\Models\BlogPost;
use App\Models\Course;
use App\Models\Flashcard;
use App\Models\FlashcardDeck;
use App\Models\Note;
use App\Models\Plan;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\Subject;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\AdminActivityLog;
use App\Models\TelegramAdmin;
use App\Models\TelegramChatSession;
use App\Models\Video;
use App\Services\FreeItemDesignationService;
use App\Support\Slug;
use App\Support\StructuredMessageParser;
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
                'admin' => $this->sendAdministrationHelp($chatId),
                'appearance' => $this->sendAppearanceHelp($chatId),
                'dashboard' => $this->sendDashboard($chatId),
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
                'users' => $this->listUsers($chatId),
                'activity' => $this->listActivity($chatId),
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
                'course' => $this->startCourseWorkflow($chatId, $telegramUserId, null),
                'video' => $this->startVideoWorkflow($chatId, $telegramUserId, null),
                'note' => $this->startNoteWorkflow($chatId, $telegramUserId, null),
                'deck' => $this->startDeckWorkflow($chatId, $telegramUserId, null),
                'quiz' => $this->startQuizWorkflow($chatId, $telegramUserId, null),
                'card' => $this->startCardWorkflow($chatId, $telegramUserId, ['deck_id' => (int) $b]),
                'question' => $this->startQuestionWorkflow($chatId, $telegramUserId, ['quiz_id' => (int) $b]),
                'subject' => $this->startSubjectWorkflow($chatId, $telegramUserId, null),
                default => $this->sendWelcome($chatId),
            };

            return;
        }

        if ($verb === 'edit') {
            match ($a) {
                'plan' => $this->startPlanWorkflow($chatId, $telegramUserId, (int) $b),
                'blog' => $this->startBlogWorkflow($chatId, $telegramUserId, (int) $b),
                'course' => $this->startCourseWorkflow($chatId, $telegramUserId, (int) $b),
                'video' => $this->startVideoWorkflow($chatId, $telegramUserId, (int) $b),
                'note' => $this->startNoteWorkflow($chatId, $telegramUserId, (int) $b),
                'deck' => $this->startDeckWorkflow($chatId, $telegramUserId, (int) $b),
                'quiz' => $this->startQuizWorkflow($chatId, $telegramUserId, (int) $b),
                'card' => $this->startCardEditWorkflow($chatId, $telegramUserId, (int) $b),
                'question' => $this->startQuestionEditWorkflow($chatId, $telegramUserId, (int) $b),
                'subject' => $this->startSubjectWorkflow($chatId, $telegramUserId, (int) $b),
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

        if ($verb === 'coverremove') {
            $this->confirmCoverRemoval($chatId, (string) $a, (int) $b);

            return;
        }

        if ($verb === 'confirmcoverremove') {
            $this->removeCover($chatId, (string) $a, (int) $b);

            return;
        }

        if ($verb === 'appearance') {
            match ($a) {
                'logo' => $this->startAppearanceImageWorkflow($chatId, $telegramUserId, 'appearance.logo'),
                'hero' => $this->startAppearanceImageWorkflow($chatId, $telegramUserId, 'appearance.hero'),
                'alt' => $this->startAppearanceAltWorkflow($chatId, $telegramUserId),
                'remove_logo' => $this->confirmAppearanceRemoval($chatId, 'logo'),
                'remove_hero' => $this->confirmAppearanceRemoval($chatId, 'hero'),
                default => $this->sendAppearanceHelp($chatId),
            };

            return;
        }

        if ($verb === 'confirmappearance') {
            $this->removeAppearanceImage($chatId, (string) $a);

            return;
        }

        if ($verb === 'useraction') {
            $this->confirmUserAction($chatId, (int) $a, (string) $b, (string) $c);

            return;
        }

        if ($verb === 'confirmuser') {
            $this->applyUserAction($chatId, (int) $a, (string) $b, (string) $c);

            return;
        }

        if ($verb === 'free') {
            $this->toggleFreeDesignation($chatId, (string) $a, (int) $b, (string) $c === '1');

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
            'subject_edit' => $this->startSubjectWorkflow($chatId, $telegramUserId, $this->requiredId($argument, 'شناسه درس‌نامه را وارد کنید.')),
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
            'users' => $this->listUsers($chatId),
            'user_find' => $this->findUsers($chatId, $argument),
            'user_manage' => $this->openUserById($chatId, $this->requiredId($argument, 'شناسه کاربر را وارد کنید.')),
            'activity' => $this->listActivity($chatId),
            'appearance' => $this->sendAppearanceHelp($chatId),
            'dashboard' => $this->sendDashboard($chatId),
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
            'مدیریت کاربران' => $this->listUsers($chatId),
            'ظاهر سایت' => $this->sendAppearanceHelp($chatId),
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
            'course.form' => $this->submitCourseForm($session, (string) ($message['text'] ?? '')),
            'video.form' => $this->submitVideoForm($session, $message),
            'note.form' => $this->submitNoteForm($session, $message),
            'deck.form' => $this->submitDeckForm($session, (string) ($message['text'] ?? '')),
            'card.form' => $this->submitCardForm($session, (string) ($message['text'] ?? '')),
            'quiz.form' => $this->submitQuizForm($session, (string) ($message['text'] ?? '')),
            'question.form' => $this->submitQuestionForm($session, (string) ($message['text'] ?? '')),
            'course.cover' => $this->submitCourseCover($session, $message),
            'blog.cover' => $this->submitBlogCover($session, $message),
            'subject.form' => $this->submitSubjectForm($session, (string) ($message['text'] ?? '')),
            'appearance.logo', 'appearance.hero' => $this->submitAppearanceImage($session, $message),
            'appearance.alt' => $this->submitAppearanceAlt($session, (string) ($message['text'] ?? '')),
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
        $this->api->sendMessage($chatId, 'مدیریت دوره‌ها و شاخه‌های آموزشی.', [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('📚 لیست سابجکت‌ها', 'list:subjects'), $this->button('🎓 لیست دوره‌ها', 'list:courses')],
                [$this->button('➕ درس‌نامه جدید', 'new:subject'), $this->button('➕ دوره جدید', 'new:course')],
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

    private function sendAdministrationHelp(int $chatId): bool
    {
        $this->api->sendMessage($chatId, "👥 مدیریت کاربران و گزارش‌های عملیاتی\nوضعیت حساب، نقش مدیر و آخرین فعالیت‌ها را از این بخش کنترل کنید.", [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('👥 کاربران', 'list:users'), $this->button('📊 آمار سایت', 'menu:dashboard')],
                [$this->button('🧾 آخرین فعالیت‌ها', 'list:activity')],
                [$this->button('↩️ بازگشت', 'menu:main')],
            ]),
        ]);

        return true;
    }

    private function sendAppearanceHelp(int $chatId): bool
    {
        $settings = SiteSetting::current();
        $this->api->sendMessage($chatId, implode("\n", [
            '🎨 مدیریت ظاهر سایت',
            'لوگو: '.($settings->logo_image_path ? 'سفارشی' : 'نشان پیش‌فرض بروکا'),
            'تصویر اصلی: '.($settings->hero_image_path ? 'سفارشی' : 'تصویر پیش‌فرض'),
            'متن جایگزین: '.$settings->hero_image_alt,
            '',
            'برای جایگزینی، دکمه مربوط را بزنید و تصویر را ارسال کنید.',
        ]), [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('🖼 تغییر لوگو', 'appearance:logo'), $this->button('🌄 تغییر تصویر اصلی', 'appearance:hero')],
                [$this->button('✍️ ویرایش توضیح تصویر', 'appearance:alt')],
                [$this->button('🗑 حذف لوگوی سفارشی', 'appearance:remove_logo'), $this->button('🗑 حذف تصویر سفارشی', 'appearance:remove_hero')],
                [$this->button('↩️ بازگشت', 'menu:main')],
            ]),
        ]);

        return true;
    }

    private function sendDashboard(int $chatId): bool
    {
        $this->api->sendMessage($chatId, implode("\n", [
            '📊 خلاصه وضعیت بروکا',
            'کاربران: '.number_format(User::query()->count()),
            'کاربران فعال: '.number_format(User::query()->where('status', 'active')->count()),
            'دوره‌ها: '.number_format(Course::query()->count()),
            'دوره‌های منتشرشده: '.number_format(Course::query()->published()->count()),
            'ویدیوها: '.number_format(Video::query()->count()),
            'مقاله‌ها: '.number_format(BlogPost::query()->count()),
        ]), [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('👥 مدیریت کاربران', 'list:users'), $this->button('🧾 فعالیت‌ها', 'list:activity')],
                [$this->button('↩️ مدیریت سایت', 'menu:admin')],
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
        $lines = ['📚 درس‌نامه‌ها:'];
        $buttons = [
            [$this->button('➕ درس‌نامه جدید', 'new:subject'), $this->button('↩️ منوی دوره‌ها', 'menu:courses')],
        ];

        foreach ($subjects as $subject) {
            $lines[] = sprintf('#%d | %s | %d دوره | %s', $subject->id, $subject->name, $subject->courses_count, $subject->is_visible ? 'نمایان' : 'پنهان');
            $buttons[] = [$this->button('🛠 درس‌نامه #'.$subject->id.' — '.$this->truncate($subject->name, 28), 'manage:subject:'.$subject->id)];
        }

        if ($subjects->isEmpty()) {
            $lines[] = 'هنوز درس‌نامه‌ای ثبت نشده است.';
        }

        $this->api->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => $this->inlineKeyboard($buttons),
        ]);

        return true;
    }

    private function listUsers(int $chatId): bool
    {
        $users = User::query()->latest('id')->limit(15)->get();
        $lines = ['👥 آخرین کاربران:', 'برای جستجو بفرستید: /user_find نام، ایمیل یا موبایل'];
        $buttons = [[$this->button('📊 آمار سایت', 'menu:dashboard'), $this->button('↩️ مدیریت سایت', 'menu:admin')]];

        foreach ($users as $user) {
            $lines[] = sprintf('#%d | %s | %s | %s', $user->id, $user->name, $user->status === 'active' ? 'فعال' : 'تعلیق', $user->is_admin ? 'مدیر' : 'فراگیر');
            $buttons[] = [$this->button('🛠 کاربر #'.$user->id.' — '.$this->truncate($user->name, 26), 'manage:user:'.$user->id)];
        }

        if ($users->isEmpty()) {
            $lines[] = 'هنوز کاربری ثبت نشده است.';
        }

        $this->api->sendMessage($chatId, implode("\n", $lines), ['reply_markup' => $this->inlineKeyboard($buttons)]);

        return true;
    }

    private function findUsers(int $chatId, string $term): bool
    {
        $term = trim($term);
        if ($term === '') {
            throw new RuntimeException('پس از دستور، نام، ایمیل، موبایل یا شناسه کاربر را وارد کنید. نمونه: /user_find علی');
        }
        $escaped = '%'.addcslashes($term, '\\%_').'%';
        $users = User::query()->where(function ($query) use ($term, $escaped): void {
            $query->where('name', 'like', $escaped)
                ->orWhere('email', 'like', $escaped)
                ->orWhere('phone', 'like', $escaped);
            if (ctype_digit($term)) {
                $query->orWhereKey((int) $term);
            }
        })->latest('id')->limit(15)->get();

        $lines = ['🔎 نتیجه جستجوی کاربران برای «'.$term.'»:'];
        $buttons = [[$this->button('↩️ همه کاربران', 'list:users')]];
        foreach ($users as $user) {
            $lines[] = sprintf('#%d | %s | %s', $user->id, $user->name, $user->email);
            $buttons[] = [$this->button('🛠 مدیریت #'.$user->id.' — '.$this->truncate($user->name, 25), 'manage:user:'.$user->id)];
        }
        if ($users->isEmpty()) {
            $lines[] = 'کاربری پیدا نشد.';
        }
        $this->api->sendMessage($chatId, implode("\n", $lines), ['reply_markup' => $this->inlineKeyboard($buttons)]);

        return true;
    }

    private function openUserById(int $chatId, int $id): bool
    {
        $this->showUserManageMenu($chatId, User::query()->findOrFail($id));

        return true;
    }

    private function listActivity(int $chatId): bool
    {
        $logs = AdminActivityLog::query()->latest('id')->limit(12)->get();
        $lines = ['🧾 آخرین فعالیت‌های مدیریتی:'];
        foreach ($logs as $log) {
            $lines[] = sprintf('%s | %s | %s', $log->created_at?->format('Y-m-d H:i') ?? '—', $log->actor_name_snapshot ?: 'مدیر تلگرام', $this->truncate($log->action, 55));
        }
        if ($logs->isEmpty()) {
            $lines[] = 'هنوز فعالیتی ثبت نشده است.';
        }

        $this->api->sendMessage($chatId, implode("\n", $lines), [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('🔄 تازه‌سازی', 'list:activity'), $this->button('↩️ مدیریت سایت', 'menu:admin')],
            ]),
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

    private function startSubjectWorkflow(int $chatId, int $telegramUserId, ?int $id): bool
    {
        $subject = $id ? Subject::query()->findOrFail($id) : null;
        $this->storeSession($chatId, $telegramUserId, 'subject.form', ['id' => $subject?->id]);
        $this->api->sendMessage($chatId, $this->subjectTemplate($subject), [
            'reply_markup' => $this->workflowMarkup('list:subjects'),
        ]);

        return true;
    }

    private function startAppearanceImageWorkflow(int $chatId, int $telegramUserId, string $workflow): bool
    {
        $this->storeSession($chatId, $telegramUserId, $workflow);
        $label = $workflow === 'appearance.logo' ? 'لوگوی جدید' : 'تصویر اصلی جدید';
        $this->api->sendMessage($chatId, "🖼 {$label} را به‌صورت عکس یا فایل تصویری ارسال کنید.\nفرمت‌های مجاز: JPG، PNG و WebP. برای لغو /cancel را بفرستید.", [
            'reply_markup' => $this->workflowMarkup('menu:appearance'),
        ]);

        return true;
    }

    private function startAppearanceAltWorkflow(int $chatId, int $telegramUserId): bool
    {
        $this->storeSession($chatId, $telegramUserId, 'appearance.alt');
        $this->api->sendMessage($chatId, "توصیف کوتاه و دقیق تصویر اصلی را به فارسی ارسال کنید.\nنمونه: ماکت آموزشی قلب روی پایه سفید", [
            'reply_markup' => $this->workflowMarkup('menu:appearance'),
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

        $this->auditTelegram($session, 'ذخیره پلن');
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

        $this->auditTelegram($session, 'ذخیره مقاله');
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

        $this->auditTelegram($session, 'ذخیره دوره');
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

        $this->auditTelegram($session, 'ذخیره ویدیو');
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

        $this->auditTelegram($session, 'ذخیره جزوه');
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

        $this->auditTelegram($session, 'ذخیره دِک فلش‌کارت');
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
        $this->auditTelegram($session, 'ذخیره فلش‌کارت');
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

        $this->auditTelegram($session, 'ذخیره آزمون');
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
        $this->auditTelegram($session, 'ذخیره سؤال آزمون');
        $this->finish($session, '✅ سؤال آزمون ذخیره شد.');
    }

    private function submitSubjectForm(TelegramChatSession $session, string $text): void
    {
        $data = StructuredMessageParser::parse($text);
        $subject = isset($session->context['id']) ? Subject::query()->findOrFail((int) $session->context['id']) : new Subject();
        $validated = Validator::make([
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'sort_order' => $this->nullableInt($data['sort_order'] ?? 0),
            'is_visible' => $data['is_visible'] ?? 'بله',
        ], [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['nullable'],
        ])->validate();

        $subject->name = $validated['name'];
        $subject->description = $this->nullableString($validated['description'] ?? null);
        $subject->sort_order = $this->nullableInt($validated['sort_order'] ?? null) ?? 0;
        $subject->is_visible = $this->nullableBool($validated['is_visible'] ?? null) ?? true;
        if (! $subject->exists || $subject->isDirty('name')) {
            $subject->slug = Slug::unique($subject->name, fn (string $slug): bool => Subject::withTrashed()->where('slug', $slug)->when($subject->exists, fn ($query) => $query->whereKeyNot($subject->id))->exists());
        }
        $subject->save();
        $this->auditTelegram($session, ($subject->wasRecentlyCreated ? 'ایجاد' : 'ویرایش').' درس‌نامه #'.$subject->id);
        $this->finish($session, '✅ درس‌نامه با موفقیت ذخیره شد.');
    }

    private function submitAppearanceImage(TelegramChatSession $session, array $message): void
    {
        $settings = SiteSetting::current();
        $isLogo = $session->workflow === 'appearance.logo';
        $field = $isLogo ? 'logo_image_path' : 'hero_image_path';
        $directory = $isLogo ? 'site-assets/logo' : 'site-assets/hero';
        $newPath = $this->storeTelegramImage($message, $directory, $isLogo ? 'logo' : 'hero');
        $this->deletePublicUrl($settings->{$field});
        $settings->{$field} = $newPath;
        $settings->save();
        $this->auditTelegram($session, $isLogo ? 'تغییر لوگوی سایت' : 'تغییر تصویر اصلی سایت');
        $this->finish($session, $isLogo ? '✅ لوگوی سایت تغییر کرد.' : '✅ تصویر اصلی صفحه نخست تغییر کرد.');
    }

    private function submitAppearanceAlt(TelegramChatSession $session, string $text): void
    {
        $text = trim($text);
        if ($text === '' || mb_strlen($text) > 255) {
            throw ValidationException::withMessages(['description' => 'توضیح تصویر باید بین ۱ تا ۲۵۵ نویسه باشد.']);
        }
        SiteSetting::current()->update(['hero_image_alt' => $text]);
        $this->auditTelegram($session, 'ویرایش توضیح تصویر اصلی سایت');
        $this->finish($session, '✅ توضیح تصویر اصلی به‌روزرسانی شد.');
    }

    private function submitCourseCover(TelegramChatSession $session, array $message): void
    {
        $course = Course::query()->findOrFail((int) ($session->context['id'] ?? 0));
        $newPath = $this->storeTelegramImage($message, 'course-covers', $course->slug ?: 'course');
        $this->deletePublicUrl($course->cover_image_path);
        $course->cover_image_path = $newPath;
        $course->save();

        $this->auditTelegram($session, 'تغییر تصویر دوره');
        $this->finish($session, '✅ تصویر دوره به‌روزرسانی شد.');
    }

    private function submitBlogCover(TelegramChatSession $session, array $message): void
    {
        $post = BlogPost::query()->findOrFail((int) ($session->context['id'] ?? 0));
        $newPath = $this->storeTelegramImage($message, 'blog-covers', $post->slug ?: 'blog');
        $this->deletePublicUrl($post->cover_image_path);
        $post->cover_image_path = $newPath;
        $post->save();

        $this->auditTelegram($session, 'تغییر تصویر مقاله');
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
            'course' => $this->showCourseManageMenu($chatId, Course::query()->findOrFail($id)),
            'video' => $this->showVideoManageMenu($chatId, Video::query()->findOrFail($id)),
            'note' => $this->showNoteManageMenu($chatId, Note::query()->findOrFail($id)),
            'deck' => $this->showDeckManageMenu($chatId, FlashcardDeck::query()->findOrFail($id)),
            'quiz' => $this->showQuizManageMenu($chatId, Quiz::query()->findOrFail($id)),
            'card' => $this->showCardManageMenu($chatId, Flashcard::query()->findOrFail($id)),
            'question' => $this->showQuestionManageMenu($chatId, QuizQuestion::query()->findOrFail($id)),
            'subject' => $this->showSubjectManageMenu($chatId, Subject::query()->findOrFail($id)),
            'user' => $this->showUserManageMenu($chatId, User::query()->findOrFail($id)),
            default => $this->sendWelcome($chatId),
        };
    }

    private function showSubjectManageMenu(int $chatId, Subject $subject): void
    {
        $this->api->sendMessage($chatId, implode("\n", [
            '📚 مدیریت درس‌نامه #'.$subject->id,
            $subject->name,
            'وضعیت نمایش: '.($subject->is_visible ? 'نمایان' : 'پنهان'),
            'تعداد دوره‌ها: '.$subject->courses()->count(),
        ]), ['reply_markup' => $this->inlineKeyboard([
            [$this->button('✏️ ویرایش', 'edit:subject:'.$subject->id), $this->button('🗑 حذف', 'delete:subject:'.$subject->id)],
            [$this->button('↩️ لیست درس‌نامه‌ها', 'list:subjects')],
        ])]);
    }

    private function showUserManageMenu(int $chatId, User $user): void
    {
        $this->api->sendMessage($chatId, implode("\n", [
            '👤 مدیریت کاربر #'.$user->id,
            'نام: '.$user->name,
            'ایمیل: '.$user->email,
            'موبایل: '.($user->phone ?: '—'),
            'وضعیت: '.($user->status === 'active' ? 'فعال' : 'تعلیق‌شده'),
            'نقش: '.($user->is_admin ? 'مدیر' : 'فراگیر'),
            'تأیید ایمیل: '.($user->email_verified_at ? 'بله' : 'خیر'),
            'ثبت‌نام در دوره‌ها: '.$user->enrollments()->count(),
            'اشتراک‌ها: '.$user->subscriptions()->count(),
            'تلاش‌های آزمون: '.$user->quizAttempts()->count(),
        ]), ['reply_markup' => $this->inlineKeyboard([
            [$this->button($user->status === 'active' ? '⛔️ تعلیق حساب' : '✅ فعال‌سازی حساب', 'useraction:'.$user->id.':status:'.($user->status === 'active' ? 'suspended' : 'active'))],
            [$this->button($user->is_admin ? '👤 سلب نقش مدیر' : '🛡 اعطای نقش مدیر', 'useraction:'.$user->id.':admin:'.($user->is_admin ? '0' : '1'))],
            [$this->button('↩️ لیست کاربران', 'list:users')],
        ])]);
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
            [$this->button('✏️ ویرایش', 'edit:blog:'.$post->id), $this->button('🖼 تغییر تصویر', 'cover:blog:'.$post->id)],
            [$this->button('🧹 حذف تصویر شاخص', 'coverremove:blog:'.$post->id)],
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
            [$this->button('✏️ ویرایش', 'edit:course:'.$course->id), $this->button('🖼 تغییر کاور', 'cover:course:'.$course->id)],
            [$this->button('🧹 حذف کاور', 'coverremove:course:'.$course->id)],
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
            [$this->button($video->is_free_designated ? '🔒 حذف از رایگان‌ها' : '🎁 انتخاب به‌عنوان رایگان', 'free:video:'.$video->id.':'.($video->is_free_designated ? '0' : '1'))],
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
            [$this->button($note->is_free_designated ? '🔒 حذف از رایگان‌ها' : '🎁 انتخاب به‌عنوان رایگان', 'free:note:'.$note->id.':'.($note->is_free_designated ? '0' : '1'))],
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
            [$this->button($card->is_free_designated ? '🔒 حذف از رایگان‌ها' : '🎁 انتخاب به‌عنوان رایگان', 'free:card:'.$card->id.':'.($card->is_free_designated ? '0' : '1'))],
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
            [$this->button($question->is_free_designated ? '🔒 حذف از رایگان‌ها' : '🎁 انتخاب به‌عنوان رایگان', 'free:question:'.$question->id.':'.($question->is_free_designated ? '0' : '1'))],
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

    private function confirmCoverRemoval(int $chatId, string $entity, int $id): void
    {
        if (! in_array($entity, ['course', 'blog'], true)) {
            throw new RuntimeException('نوع تصویر شاخص نامعتبر است.');
        }
        $this->api->sendMessage($chatId, 'آیا از حذف تصویر شاخص مطمئن هستید؟', [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('✅ بله، تصویر حذف شود', 'confirmcoverremove:'.$entity.':'.$id)],
                [$this->button('↩️ انصراف', 'manage:'.$entity.':'.$id)],
            ]),
        ]);
    }

    private function removeCover(int $chatId, string $entity, int $id): void
    {
        $model = match ($entity) {
            'course' => Course::query()->findOrFail($id),
            'blog' => BlogPost::query()->findOrFail($id),
            default => throw new RuntimeException('نوع تصویر شاخص نامعتبر است.'),
        };
        $this->deletePublicUrl($model->cover_image_path);
        $model->cover_image_path = null;
        $model->save();
        $this->auditTelegramByChat($chatId, 'حذف تصویر شاخص '.$this->entityLabel($entity).' #'.$id);
        $this->showManageMenu($chatId, $entity, $id);
    }

    private function confirmAppearanceRemoval(int $chatId, string $type): void
    {
        $label = $type === 'logo' ? 'لوگوی سفارشی' : 'تصویر اصلی سفارشی';
        $this->api->sendMessage($chatId, "آیا از حذف {$label} و بازگشت به حالت پیش‌فرض مطمئن هستید؟", [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('✅ بله، حذف شود', 'confirmappearance:'.$type)],
                [$this->button('↩️ انصراف', 'menu:appearance')],
            ]),
        ]);
    }

    private function removeAppearanceImage(int $chatId, string $type): void
    {
        if (! in_array($type, ['logo', 'hero'], true)) {
            throw new RuntimeException('نوع تصویر نامعتبر است.');
        }
        $settings = SiteSetting::current();
        $field = $type === 'logo' ? 'logo_image_path' : 'hero_image_path';
        $this->deletePublicUrl($settings->{$field});
        $settings->{$field} = null;
        $settings->save();
        $this->auditTelegramByChat($chatId, $type === 'logo' ? 'حذف لوگوی سفارشی سایت' : 'حذف تصویر اصلی سفارشی سایت');
        $this->sendAppearanceHelp($chatId);
    }

    private function confirmUserAction(int $chatId, int $userId, string $field, string $value): void
    {
        $user = User::query()->findOrFail($userId);
        $label = match ([$field, $value]) {
            ['status', 'active'] => 'فعال‌سازی حساب',
            ['status', 'suspended'] => 'تعلیق حساب',
            ['admin', '1'] => 'اعطای نقش مدیر',
            ['admin', '0'] => 'سلب نقش مدیر',
            default => throw new RuntimeException('عملیات کاربر نامعتبر است.'),
        };
        $this->api->sendMessage($chatId, "آیا «{$label}» برای کاربر {$user->name} را تأیید می‌کنید؟", [
            'reply_markup' => $this->inlineKeyboard([
                [$this->button('✅ تأیید نهایی', "confirmuser:{$userId}:{$field}:{$value}")],
                [$this->button('↩️ انصراف', 'manage:user:'.$userId)],
            ]),
        ]);
    }

    private function applyUserAction(int $chatId, int $userId, string $field, string $value): void
    {
        $user = User::query()->findOrFail($userId);
        if ($field === 'status' && in_array($value, ['active', 'suspended'], true)) {
            $user->forceFill(['status' => $value])->save();
        } elseif ($field === 'admin' && in_array($value, ['0', '1'], true)) {
            if ($user->is_admin && $value === '0' && User::query()->where('is_admin', true)->count() <= 1) {
                throw new RuntimeException('حداقل یک مدیر باید در سیستم باقی بماند.');
            }
            $user->forceFill(['is_admin' => $value === '1'])->save();
        } else {
            throw new RuntimeException('عملیات کاربر نامعتبر است.');
        }

        $this->auditTelegramByChat($chatId, 'ویرایش کاربر #'.$user->id.' از تلگرام');
        $this->showUserManageMenu($chatId, $user->fresh());
    }

    private function toggleFreeDesignation(int $chatId, string $entity, int $id, bool $designated): void
    {
        $item = match ($entity) {
            'video' => Video::query()->findOrFail($id),
            'note' => Note::query()->findOrFail($id),
            'card' => Flashcard::query()->findOrFail($id),
            'question' => QuizQuestion::query()->findOrFail($id),
            default => throw new RuntimeException('نوع محتوای رایگان نامعتبر است.'),
        };
        $this->freeItems->set($item, $designated);
        $this->auditTelegramByChat($chatId, ($designated ? 'انتخاب' : 'حذف').' محتوای رایگان '.$entity.' #'.$id);
        $this->showManageMenu($chatId, $entity, $id);
    }

    private function deletePublicUrl(?string $url): void
    {
        if ($url && str_contains($url, '/storage/')) {
            Storage::disk('public')->delete((string) str($url)->after('/storage/'));
        }
    }

    private function auditTelegram(TelegramChatSession $session, string $action): void
    {
        $this->auditTelegramByChat((int) $session->telegram_chat_id, $action, (int) $session->telegram_user_id);
    }

    private function auditTelegramByChat(int $chatId, string $action, ?int $telegramUserId = null): void
    {
        try {
            $telegramUserId ??= (int) (TelegramChatSession::query()->where('telegram_chat_id', $chatId)->value('telegram_user_id') ?? 0);
            $admin = TelegramAdmin::query()->where('telegram_user_id', $telegramUserId)->first();
            AdminActivityLog::create([
                'actor_name_snapshot' => trim(($admin?->first_name ?? 'مدیر تلگرام').' '.($admin?->last_name ?? '')),
                'actor_email_snapshot' => $admin?->username ? '@'.$admin->username : null,
                'action' => mb_substr('تلگرام: '.$action, 0, 255),
                'route_name' => 'telegram.webhook',
                'method' => 'BOT',
                'url' => 'telegram/webhook',
                'payload' => ['telegram_user_id' => $telegramUserId ?: null, 'chat_id' => $chatId],
                'status_code' => 200,
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
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

        if ($entity === 'subject' && $model->courses()->exists()) {
            throw new RuntimeException('این درس‌نامه دارای دوره است؛ ابتدا دوره‌ها را منتقل یا حذف کنید.');
        }

        try {
            $entity === 'blog' ? $model->forceDelete() : $model->delete();
        } catch (Throwable $exception) {
            $this->api->sendMessage($chatId, '❌ حذف انجام نشد: '.$this->truncate($exception->getMessage(), 260), [
                'reply_markup' => $this->inlineKeyboard([
                    [$this->button('↩️ بازگشت', 'manage:'.$entity.':'.$id)],
                ]),
            ]);

            return;
        }

        $this->auditTelegramByChat($chatId, 'حذف '.$this->entityLabel($entity).' #'.$id);
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

        $this->auditTelegramByChat($chatId, 'تغییر وضعیت '.$this->entityLabel($entity).' #'.$id.' به '.$this->statusLabel($target));
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
            'course' => Course::query()->findOrFail($id),
            'video' => Video::query()->findOrFail($id),
            'note' => Note::query()->findOrFail($id),
            'deck' => FlashcardDeck::query()->findOrFail($id),
            'card' => Flashcard::query()->findOrFail($id),
            'quiz' => Quiz::query()->findOrFail($id),
            'question' => QuizQuestion::query()->findOrFail($id),
            'subject' => Subject::query()->findOrFail($id),
            default => throw new RuntimeException('آیتم موردنظر پیدا نشد.'),
        };
    }

    private function indexCallback(string $entity): string
    {
        return match ($entity) {
            'plan' => 'list:plans',
            'blog' => 'list:blogs',
            'course' => 'list:courses',
            'video' => 'list:videos',
            'note' => 'list:notes',
            'deck' => 'list:decks',
            'quiz' => 'list:quizzes',
            'card' => 'list:decks',
            'question' => 'list:quizzes',
            'subject' => 'list:subjects',
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
            'course' => 'دوره',
            'video' => 'ویدیو',
            'note' => 'جزوه',
            'deck' => 'دِک فلش‌کارت',
            'card' => 'فلش‌کارت',
            'quiz' => 'آزمون',
            'question' => 'سؤال',
            'subject' => 'درس‌نامه',
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
            [$this->button('🧪 آزمون و فلش‌کارت', 'menu:study'), $this->button('🎨 ظاهر سایت', 'menu:appearance')],
            [$this->button('👥 کاربران و گزارش‌ها', 'menu:admin')],
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

        $normalized = strtr(trim((string) $value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return (int) $normalized;
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

        $filename = Slug::unique($title.'-'.now()->format('YmdHis'), fn (string $candidate): bool => file_exists(public_path('videos/'.$candidate.'.'.$extension))).'.'.$extension;
        $target = public_path('videos/'.$filename);
        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0775, true);
        }

        rename($tmp, $target);

        return $target;
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

        if (filesize($tmp) > 10 * 1024 * 1024) {
            @unlink($tmp);
            throw new RuntimeException('حجم تصویر نباید بیشتر از ۱۰ مگابایت باشد.');
        }

        $image = @getimagesize($tmp);
        $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = is_array($image) ? (string) ($image['mime'] ?? '') : '';
        if (! isset($allowedMimes[$mime])) {
            @unlink($tmp);
            throw new RuntimeException('فایل ارسالی تصویر معتبر JPG، PNG یا WebP نیست.');
        }
        $extension = $allowedMimes[$mime];

        $filename = Slug::unique($name.'-'.now()->format('YmdHis'), fn (string $candidate): bool => Storage::disk('public')->exists(trim($directory, '/').'/'.$candidate.'.'.$extension)).'.'.$extension;
        $storagePath = trim($directory, '/').'/'.$filename;
        $stream = fopen($tmp, 'r');
        $stored = $stream !== false && Storage::disk('public')->put($storagePath, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
        @unlink($tmp);
        if (! $stored) {
            throw new RuntimeException('ذخیره تصویر ناموفق بود؛ دوباره تلاش کنید.');
        }

        return Storage::disk('public')->url($storagePath);
    }

    private function subjectTemplate(?Subject $subject): string
    {
        return implode("\n", [
            $subject ? '✏️ ویرایش درس‌نامه #'.$subject->id : '➕ ایجاد درس‌نامه جدید',
            'فرم زیر را کپی کنید، مقدارها را تغییر دهید و همان‌جا بفرستید:',
            '',
            'نام: '.$this->display($subject?->name),
            'توضیحات: '.$this->display($subject?->description),
            'ترتیب: '.$this->display($subject?->sort_order ?? 0),
            'نمایان: '.$this->boolText($subject?->is_visible ?? true),
            '',
            'راهنما: برای نمایش «بله» و برای پنهان‌کردن «خیر» بنویسید.',
        ]);
    }

    private function planTemplate(?Plan $plan): string
    {
        return implode("\n", [
            'ارسال/ویرایش پلن:',
            'شناسه: '.$this->display($plan?->id),
            'کد: '.$this->display($plan?->code),
            'نام: '.$this->display($plan?->name),
            'توضیحات: '.$this->display($plan?->description),
            'قیمت_ریال: '.$this->display($plan?->price_irr),
            'مدت_ماه: '.$this->display($plan?->duration_months),
            'فعال: '.$this->boolText($plan?->is_active),
            'ترتیب: '.$this->display($plan?->sort_order),
        ]);
    }

    private function blogTemplate(?BlogPost $post): string
    {
        return implode("\n", [
            'ارسال/ویرایش مطلب وبلاگ:',
            'شناسه: '.$this->display($post?->id),
            'عنوان: '.$this->display($post?->title),
            'دسته‌بندی: '.$this->display($post?->category),
            'نام_نویسنده: '.$this->display($post?->author_name),
            'نام_بازبین: '.$this->display($post?->reviewer_name),
            'خلاصه: '.$this->display($post?->excerpt),
            'وضعیت: '.$this->statusLabel($post?->status ?? 'draft'),
            'تاریخ_انتشار: '.$this->dateText($post?->published_at),
            'عنوان_سئو: '.$this->display($post?->meta_title),
            'توضیح_سئو: '.$this->display($post?->meta_description),
            'تصویر_شاخص: '.$this->display($post?->cover_image_path),
            '[محتوا]',
            $this->display($post?->content),
            '[/محتوا]',
        ]);
    }

    private function courseTemplate(?Course $course): string
    {
        return implode("\n", [
            'ارسال/ویرایش دوره:',
            'شناسه: '.$this->display($course?->id),
            'شناسه_درس‌نامه: '.$this->display($course?->subject_id),
            'عنوان: '.$this->display($course?->title),
            'خلاصه: '.$this->display($course?->excerpt),
            'سطح: '.$this->display($course?->level),
            'شناسه_نویسنده: '.$this->display($course?->author_id),
            'شناسه_بازبین: '.$this->display($course?->reviewer_id),
            'ترتیب: '.$this->display($course?->sort_order),
            'وضعیت: '.$this->statusLabel($course?->status ?? 'draft'),
            'تاریخ_انتشار: '.$this->dateText($course?->published_at),
            'تصویر_شاخص: '.$this->display($course?->cover_image_path),
            '[توضیحات]',
            $this->display($course?->description),
            '[/توضیحات]',
        ]);
    }

    private function videoTemplate(?Video $video): string
    {
        return implode("\n", [
            'ارسال/ویرایش ویدیو:',
            'شناسه: '.$this->display($video?->id),
            'شناسه_دوره: '.$this->display($video?->course_id),
            'عنوان: '.$this->display($video?->title),
            'توضیحات: '.$this->display($video?->description),
            'مدت_ثانیه: '.$this->display($video?->duration_seconds),
            'آستانه_تکمیل: '.$this->display($video?->completion_threshold_percent),
            'شناسه_نویسنده: '.$this->display($video?->author_id),
            'شناسه_بازبین: '.$this->display($video?->reviewer_id),
            'ترتیب: '.$this->display($video?->sort_order),
            'وضعیت: '.$this->statusLabel($video?->status ?? 'draft'),
            'تاریخ_انتشار: '.$this->dateText($video?->published_at),
            'رایگان: '.$this->boolText($video?->is_free_designated),
            'نوع_منبع: '.($video ? 'نگه‌داری' : 'بارگذاری'),
            'نشانی_پخش: '.$this->display($video?->playback_asset_id),
        ]);
    }

    private function noteTemplate(?Note $note): string
    {
        return implode("\n", [
            'ارسال/ویرایش جزوه:',
            'شناسه: '.$this->display($note?->id),
            'شناسه_دوره: '.$this->display($note?->course_id),
            'عنوان: '.$this->display($note?->title),
            'توضیحات: '.$this->display($note?->description),
            'شناسه_نویسنده: '.$this->display($note?->author_id),
            'شناسه_بازبین: '.$this->display($note?->reviewer_id),
            'ترتیب: '.$this->display($note?->sort_order),
            'وضعیت: '.$this->statusLabel($note?->status ?? 'draft'),
            'تاریخ_انتشار: '.$this->dateText($note?->published_at),
            'رایگان: '.$this->boolText($note?->is_free_designated),
            'نوع_منبع: '.($note ? 'نگه‌داری' : 'بارگذاری'),
            'نشانی_فایل: ',
        ]);
    }

    private function deckTemplate(?FlashcardDeck $deck): string
    {
        return implode("\n", [
            'ارسال/ویرایش دِک فلش‌کارت:',
            'شناسه: '.$this->display($deck?->id),
            'شناسه_دوره: '.$this->display($deck?->course_id),
            'عنوان: '.$this->display($deck?->title),
            'توضیحات: '.$this->display($deck?->description),
            'شناسه_نویسنده: '.$this->display($deck?->author_id),
            'شناسه_بازبین: '.$this->display($deck?->reviewer_id),
            'ترتیب: '.$this->display($deck?->sort_order),
            'وضعیت: '.$this->statusLabel($deck?->status ?? 'draft'),
            'تاریخ_انتشار: '.$this->dateText($deck?->published_at),
        ]);
    }

    private function cardTemplate(?Flashcard $card, ?int $deckId): string
    {
        return implode("\n", [
            'ارسال/ویرایش فلش‌کارت:',
            'شناسه: '.$this->display($card?->id),
            'شناسه_دک: '.$this->display($card?->flashcard_deck_id ?: $deckId),
            'راهنما: '.$this->display($card?->hint),
            'ترتیب: '.$this->display($card?->sort_order),
            'وضعیت: '.$this->statusLabel($card?->status ?? 'draft'),
            'تاریخ_انتشار: '.$this->dateText($card?->published_at),
            'رایگان: '.$this->boolText($card?->is_free_designated),
            '[متن_رو]',
            $this->display($card?->front),
            '[/متن_رو]',
            '[متن_پشت]',
            $this->display($card?->back),
            '[/متن_پشت]',
        ]);
    }

    private function quizTemplate(?Quiz $quiz): string
    {
        return implode("\n", [
            'ارسال/ویرایش آزمون:',
            'شناسه: '.$this->display($quiz?->id),
            'شناسه_دوره: '.$this->display($quiz?->course_id),
            'عنوان: '.$this->display($quiz?->title),
            'توضیحات: '.$this->display($quiz?->description),
            'حدنصاب: '.$this->display($quiz?->pass_threshold_percent),
            'شناسه_نویسنده: '.$this->display($quiz?->author_id),
            'شناسه_بازبین: '.$this->display($quiz?->reviewer_id),
            'ترتیب: '.$this->display($quiz?->sort_order),
            'وضعیت: '.$this->statusLabel($quiz?->status ?? 'draft'),
            'تاریخ_انتشار: '.$this->dateText($quiz?->published_at),
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
            'شناسه: '.$this->display($question?->id),
            'شناسه_آزمون: '.$this->display($question?->quiz_id ?: $quizId),
            'منبع: '.$this->display($question?->source_citation),
            'شناسه_نویسنده: '.$this->display($question?->author_id),
            'شناسه_بازبین: '.$this->display($question?->reviewer_id),
            'ترتیب: '.$this->display($question?->sort_order),
            'وضعیت: '.$this->statusLabel($question?->status ?? 'draft'),
            'تاریخ_انتشار: '.$this->dateText($question?->published_at),
            'رایگان: '.$this->boolText($question?->is_free_designated),
            'گزینه_درست: '.$correctIndex,
            'گزینه‌ها: '.$options,
            '[صورت_سؤال]',
            $this->display($question?->prompt),
            '[/صورت_سؤال]',
            '[پاسخ_تشریحی]',
            $this->display($question?->explanation),
            '[/پاسخ_تشریحی]',
        ]);
    }

    private function boolText(?bool $value): string
    {
        return $value ? 'بله' : 'خیر';
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
