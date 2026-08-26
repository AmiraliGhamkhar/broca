<?php

namespace Database\Seeders;

use App\Models\Contributor;
use App\Models\Course;
use App\Models\Flashcard;
use App\Models\FlashcardDeck;
use App\Models\Note;
use App\Models\Plan;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\Subject;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed a local environment with realistic Persian sample content that
     * exercises the freemium limits (2 free videos, 1 free note, 10 free
     * cards, 1 free question) and the publication workflow.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'مدیر بروکا',
            'email' => 'admin@broca.test',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $this->seedPlans();

        $author = Contributor::create([
            'name' => 'دکتر سارا احمدی',
            'slug' => 'dr-sara-ahmadi',
            'credentials' => 'پزشک عمومی، کارشناس آموزش پزشکی',
            'specialty' => 'آموزش علوم پایه',
            'bio' => 'مدرس فیزیولوژی و علوم پایه پزشکی.',
            'is_visible' => true,
        ]);

        $reviewer = Contributor::create([
            'name' => 'دکتر رضا کریمی',
            'slug' => 'dr-reza-karimi',
            'credentials' => 'متخصص داخلی، عضو هیئت علمی',
            'specialty' => 'پزشکی داخلی',
            'bio' => 'بازبین علمی محتوای آموزشی بروکا.',
            'is_visible' => true,
        ]);

        $subject = Subject::create([
            'name' => 'فیزیولوژی',
            'slug' => 'physiology',
            'description' => 'کارکرد طبیعی بدن انسان؛ از سلول تا اندام.',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $course = Course::create([
            'subject_id' => $subject->id,
            'title' => 'مبانی فیزیولوژی قلب و عروق',
            'slug' => 'cardiovascular-physiology-basics',
            'excerpt' => 'مرور گام‌به‌گام الکتروفیزیولوژی قلب، چرخهٔ قلبی و تنظیم فشار خون.',
            'description' => 'این دوره برای دانشجویان پزشکی و علوم پایه طراحی شده و مفاهیم قلب و عروق را از سطح غشای سلولی تا تنظیم عصبی-هورمونی پوشش می‌دهد.',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'author_id' => $author->id,
            'reviewer_id' => $reviewer->id,
            'level' => 'مقدماتی',
            'sort_order' => 1,
        ]);

        foreach ([
            ['ساختار و کارکرد غشای سلولی قلبی', true],
            ['پتانسیل عمل و پیام‌رسانی الکتریکی', true],
            ['چرخهٔ قلبی و صداهای قلب', false],
            ['تنظیم فشار خون و حجم داخل عروقی', false],
        ] as $index => [$title, $isFree]) {
            Video::create([
                'course_id' => $course->id,
                'title' => $title,
                'slug' => 'lesson-'.($index + 1),
                'description' => 'درس '.($index + 1).' از دورهٔ مبانی فیزیولوژی قلب و عروق.',
                'sort_order' => $index + 1,
                'duration_seconds' => 900,
                'manifest_reference' => 'sample-video.mp4',
                'is_free_designated' => $isFree,
                'status' => 'published',
                'published_at' => now()->subDay(),
            ]);
        }

        Note::create([
            'course_id' => $course->id,
            'title' => 'جزوهٔ خلاصهٔ الکتروفیزیولوژی',
            'slug' => 'electrophysiology-summary',
            'description' => 'خلاصهٔ نمونه برای دانلود.',
            'sort_order' => 1,
            'storage_disk' => 'local',
            'storage_key' => 'notes/electrophysiology-summary.pdf',
            'mime_type' => 'application/pdf',
            'is_free_designated' => true,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $deck = FlashcardDeck::create([
            'course_id' => $course->id,
            'title' => 'کارت‌های مرور فیزیولوژی قلب',
            'slug' => 'cardio-review-deck',
            'description' => 'مرور فاصله‌دار مفاهیم کلیدی.',
            'sort_order' => 1,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $terms = [
            ['پتانسیل استراحت غشایی' => 'اختلاف پتانسیل پایدار حدود منفی ۹۰ میلی‌ولت در دیاستول.'],
            ['دپلاریزاسیون' => 'کاهش اختلاف پتانسیل غشا و نزدیک شدن آن به صفر یا مثبت.'],
            ['ریپلاریزاسیون' => 'بازگشت پتانسیل غشا به حالت استراحت.'],
            ['گره سینوسی-دهلیزی' => 'ضربان‌ساز طبیعی قلب با نرخ ۶۰ تا ۱۰۰ در دقیقه.'],
            ['دورهٔ تنهایی (رفرکتوری)' => 'بازهٔ غیرقابل تحریک کوتاه پس از پتانسیل عمل.'],
            ['حجم ضربه‌ای' => 'حجم خون خارج‌شده از بطن در هر انقباض.'],
            ['بازگشت وریدی' => 'جریان خون به سمت دهلیزهای راست و چپ.'],
            ['قانون فرانک-استارلینگ' => 'افزایش کشش پیش‌بار، افزایش قدرت انقباض.'],
            ['فشار خون متوسط' => 'حاصل‌ضرب برون‌ده قلبی در مقاومت محیطی کل.'],
            ['بارورسپتورها' => 'گیرنده‌های فشار در قوس آئورت و سینوس کاروتید.'],
            ['هورمون ANP' => 'پپتید دهلیزی ناتریورتیک که سدیم و فشار خون را کاهش می‌دهد.'],
            ['سیستم رنین-آنژیوتانسین' => 'مسیر هورمونی تنظیم فشار خون و حجم خون.'],
        ];

        foreach ($terms as $index => $term) {
            Flashcard::create([
                'flashcard_deck_id' => $deck->id,
                'front' => array_key_first($term),
                'back' => reset($term),
                'sort_order' => $index + 1,
                'is_free_designated' => $index < 10, // cap is 10
                'status' => 'published',
                'published_at' => now()->subDay(),
            ]);
        }

        $quiz = Quiz::create([
            'course_id' => $course->id,
            'title' => 'آزمون مرور فصل اول',
            'slug' => 'chapter-one-review-quiz',
            'description' => 'آزمون تشخیصی کوتاه.',
            'sort_order' => 1,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        foreach ([
            ['پتانسیل استراحت غشایی سلول قلبی حدوداً چند میلی‌ولت است؟', ['منفی ۹۰', 'منفی ۵۰', 'مثبت ۳۰', 'صفر'], 0, true],
            ['ضربان‌ساز طبیعی قلب کدام ساختار است؟', ['گره AV', 'گره SA', 'دستهٔ هیس', 'الیاف پورکینژه'], 1, false],
            ['قانون فرانک-استارلینگ رابطهٔ چه دو عاملی را توصیف می‌کند؟', ['پیش‌بار و قدرت انقباض', 'پس‌بار و ضربان قلب', 'هموگلوبین و اکسیژن', 'سدیم و پتانسیل عمل'], 0, false],
        ] as $index => [$prompt, $labels, $correctIndex, $isFree]) {
            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'prompt' => $prompt,
                'sort_order' => $index + 1,
                'is_free_designated' => $isFree,
                'status' => 'published',
                'published_at' => now()->subDay(),
            ]);

            foreach ($labels as $optionIndex => $label) {
                QuizOption::create([
                    'quiz_question_id' => $question->id,
                    'label' => $label,
                    'is_correct' => $optionIndex === $correctIndex,
                    'sort_order' => $optionIndex + 1,
                ]);
            }
        }
    }

    private function seedPlans(): void
    {
        $plans = [
            ['code' => 'free', 'name' => 'رایگان', 'description' => 'دسترسی به محتوای رایگان هر دوره.', 'duration_months' => 0, 'price_irr' => 0, 'sort_order' => 1],
            // Placeholder prices — set the real Toman amounts in the plans
            // table (or this seeder) once the client confirms them.
            ['code' => 'monthly', 'name' => 'یک‌ماهه', 'description' => 'دسترسی کامل به همهٔ دوره‌ها برای ۳۰ روز.', 'duration_months' => 1, 'price_irr' => 5000000, 'sort_order' => 2],
            ['code' => 'quarterly', 'name' => 'سه‌ماهه', 'description' => 'دسترسی کامل به همهٔ دوره‌ها برای ۹۰ روز.', 'duration_months' => 3, 'price_irr' => 12000000, 'sort_order' => 3],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(['code' => $plan['code']], $plan + ['is_active' => true]);
        }
    }
}
