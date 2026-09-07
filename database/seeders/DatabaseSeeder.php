<?php

namespace Database\Seeders;

use App\Models\BlogPost;
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
    public function run(): void
    {
        $this->provisionPlaceholderMedia();

        // 1. Administrators and Test Users
        $admin = User::factory()->create([
            'name' => 'دکتر مدیر سامانه',
            'email' => 'admin@broca.test',
            'phone' => '09120000000',
        ]);
        $admin->forceFill(['is_admin' => true, 'status' => 'active'])->save();

        $student = User::factory()->create([
            'name' => 'علی احمدی (دانشجوی پزشکی)',
            'email' => 'student@broca.test',
            'phone' => '09121111111',
        ]);
        $student->forceFill(['status' => 'active'])->save();

        // 2. Subscription Plans
        $this->seedPlans();

        // 3. Demo contributors (LOCAL SEED DATA ONLY — NOT REAL PEOPLE)
        //
        // These rows exist so the catalog/course pages have an author and a
        // reviewer to render against in local development and tests. They are
        // deliberately:
        //   - named "نمونه" (sample), never a plausible real doctor's name;
        //   - given process-descriptive credentials, never a claimed post at
        //     a real institution;
        //   - is_visible = false, so they never surface on the public site
        //     even if this seeder is accidentally run in production.
        //
        // SPEC.md: "No placeholder may be presented as a real medical
        // credential, source, price, provider, or launch guarantee."
        $drSara = Contributor::create([
            'name' => 'نویسندهٔ نمونه ۱',
            'slug' => 'sample-author-1',
            'credentials' => 'نمونهٔ توسعه — اعتبارنامهٔ واقعی ثبت نشده است',
            'specialty' => 'فیزیولوژی قلب و عروق',
            'bio' => 'رکورد نمونه برای محیط توسعه. پیش از انتشار عمومی باید با نویسندهٔ واقعی جایگزین شود.',
            'is_visible' => false,
        ]);

        $drReza = Contributor::create([
            'name' => 'بازبین نمونه ۱',
            'slug' => 'sample-reviewer-1',
            'credentials' => 'نمونهٔ توسعه — اعتبارنامهٔ واقعی ثبت نشده است',
            'specialty' => 'بازبینی علمی بالینی',
            'bio' => 'رکورد نمونه برای محیط توسعه. پیش از انتشار عمومی باید با بازبین واقعی جایگزین شود.',
            'is_visible' => false,
        ]);

        $drNima = Contributor::create([
            'name' => 'نویسندهٔ نمونه ۲',
            'slug' => 'sample-author-2',
            'credentials' => 'نمونهٔ توسعه — اعتبارنامهٔ واقعی ثبت نشده است',
            'specialty' => 'آناتومی بالینی',
            'bio' => 'رکورد نمونه برای محیط توسعه. پیش از انتشار عمومی باید با نویسندهٔ واقعی جایگزین شود.',
            'is_visible' => false,
        ]);

        $drMaryam = Contributor::create([
            'name' => 'نویسندهٔ نمونه ۳',
            'slug' => 'sample-author-3',
            'credentials' => 'نمونهٔ توسعه — اعتبارنامهٔ واقعی ثبت نشده است',
            'specialty' => 'علوم اعصاب و نوروآناتومی',
            'bio' => 'رکورد نمونه برای محیط توسعه. پیش از انتشار عمومی باید با نویسندهٔ واقعی جایگزین شود.',
            'is_visible' => false,
        ]);

        // 4. Subjects
        $subjPhysio = Subject::create([
            'name' => 'فیزیولوژی قلب و عروق',
            'slug' => 'cardiovascular-physiology',
            'description' => 'الکتروفیزیولوژی، چرخه قلبی، صداهای قلب و تنظیم فشار و جریان خون.',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $subjNeuro = Subject::create([
            'name' => 'نوروآناتومی و علوم اعصاب',
            'slug' => 'neuroanatomy',
            'description' => 'ساختار قشر مخ، هسته‌های قاعده‌ای، ساقه مغز و مناطق تکلم (ناحیه بروکا).',
            'sort_order' => 2,
            'is_visible' => true,
        ]);

        $subjAnatomy = Subject::create([
            'name' => 'آناتومی بالینی و اسکلتی',
            'slug' => 'clinical-anatomy',
            'description' => 'کالبدشناسی قفسه سینه، عضلات تنفسی، استخوان‌بندی و مجاورات عروقی.',
            'sort_order' => 3,
            'is_visible' => true,
        ]);

        $subjGeneral = Subject::create([
            'name' => 'فیزیولوژی سلول و مولکولی',
            'slug' => 'cellular-physiology',
            'description' => 'انتقال یون‌ها از غشا، پتانسیل عمل و سیناپس‌های عصبی-عضلانی.',
            'sort_order' => 4,
            'is_visible' => true,
        ]);

        // COURSE 1: Cardiovascular Electrophysiology
        $course1 = Course::create([
            'subject_id' => $subjPhysio->id,
            'title' => 'مبانی فیزیولوژی و الکتروفیزیولوژی قلب',
            'slug' => 'cardiovascular-physiology-basics',
            'excerpt' => 'مرور گام‌به‌گام الکتروفیزیولوژی، پتانسیل عمل میوکارد، چرخه قلبی و تنظیم فشار خون.',
            'description' => 'این دوره برای دانشجویان پزشکی و داوطلبان آزمون‌های جامع علوم پایه طراحی شده و مفاهیم گردش خون، گره‌های هدایتی SA و AV و تغییرات الکتریکی نوار قلب را پوشش می‌دهد.',
            'status' => 'published',
            'published_at' => now()->subDays(10),
            'author_id' => $drSara->id,
            'reviewer_id' => $drReza->id,
            'level' => 'پزشکی عمومی - علوم پایه',
            'sort_order' => 1,
        ]);

        $c1Videos = [
            ['ساختار و کارکرد غشای سلولی قلبی', 'lesson-1', true, 840],
            ['پتانسیل عمل و پیام‌رسانی الکتریکی گره SA و AV', 'lesson-2', true, 920],
            ['چرخهٔ قلبی، منحنی‌های فشار-حجم و صداهای قلب', 'lesson-3', false, 1100],
            ['تنظیم فشار خون و همودینامیک عروق محیطی', 'lesson-4', false, 980],
        ];
        foreach ($c1Videos as $idx => [$vTitle, $vSlug, $isFree, $dur]) {
            Video::create([
                'course_id' => $course1->id,
                'title' => $vTitle,
                'slug' => $vSlug,
                'description' => 'جلسه آموزشی ' . ($idx + 1) . ' از دوره فیزیولوژی قلب.',
                'sort_order' => $idx + 1,
                'duration_seconds' => $dur,
                'manifest_reference' => 'sample-video.mp4',
                'is_free_designated' => $isFree,
                'status' => 'published',
                'published_at' => now()->subDays(8),
                'author_id' => $drSara->id,
                'reviewer_id' => $drReza->id,
            ]);
        }

        Note::create([
            'course_id' => $course1->id,
            'title' => 'جزوهٔ خلاصهٔ الکتروفیزیولوژی و نوار قلب (PDF)',
            'slug' => 'electrophysiology-summary',
            'description' => 'خلاصه نکات کلیدی پتانسیل‌های دیاستولی و سیستولی قلب همراه با نمودارهای کاربردی.',
            'sort_order' => 1,
            'storage_disk' => 'local',
            'storage_key' => 'notes/electrophysiology-summary.pdf',
            'mime_type' => 'application/pdf',
            'is_free_designated' => true,
            'status' => 'published',
            'published_at' => now()->subDays(8),
            'author_id' => $drSara->id,
            'reviewer_id' => $drReza->id,
        ]);

        $deck1 = FlashcardDeck::create([
            'course_id' => $course1->id,
            'title' => 'کارت‌های مرور فاصله‌دار فیزیولوژی قلب',
            'slug' => 'cardio-review-deck',
            'description' => 'مرور منظم تعاریف، مقادیر عددی و قوانین فیزیولوژیک بر اساس الگوریتم SM-2.',
            'sort_order' => 1,
            'status' => 'published',
            'published_at' => now()->subDays(8),
            'author_id' => $drSara->id,
            'reviewer_id' => $drReza->id,
        ]);

        $cardioTerms = [
            ['پتانسیل استراحت غشایی سلول قلبی چقدر است؟' => 'حدود منفی ۹۰ میلی‌ولت در فاز ۴ دیاستول که توسط نفوذپذیری پتاسیم برقرار می‌ماند.'],
            ['نقش فاز ۲ (Plateau) در پتانسیل عمل بطنی چیست؟' => 'ورود یون‌های کلسیم از کانال‌های نوع L و حفظ انقباض طولانی بطن.'],
            ['ضربان‌ساز اصلی قلب کدام گره است؟' => 'گره سینوسی-دهلیزی (SA Node) با نرخ تخلیه ذاتی ۶۰ تا ۱۰۰ در دقیقه.'],
            ['قانون فرانک-استارلینگ (Frank-Starling) چه می‌گوید؟' => 'افزایش حجم پایان دیاستول (پیش‌بار) باعث کشش بیشتر فیبرهای میوکارد و افزایش قدرت انقباض می‌شود.'],
            ['صدای اول قلب (S1) ناشی از بسته شدن کدام دریچه‌هاست؟' => 'بسته شدن دریچه‌های میترال و تریکوسپید در ابتدای سیستول بطنی.'],
            ['صدای دوم قلب (S2) ناشی از چیست؟' => 'بسته شدن دریچه‌های آئورت و ریوی (سمی‌لونار) در پایان سیستول.'],
            ['کسر تخلیه‌ای (Ejection Fraction) نرمال چقدر است؟' => 'نسبت حجم ضربه‌ای به حجم پایان دیاستول که به طور نرمال بین ۵۵ تا ۷۰ درصد است.'],
            ['گیرنده‌های بارورسپتور در کجا قرار دارند؟' => 'در قوس آئورت (عصب واگ) و سینوس کاروتید (عصب گلوسوفارنژیال).'],
            ['اثر تحریک سمپاتیک بر گره SA چیست؟' => 'افزایش ورود سدیم و کلسیم، افزایش شیب دپلاریزاسیون فاز ۴ و افزایش ضربان قلب.'],
            ['پپتید ناتریورتیک دهلیزی (ANP) چه اثری دارد؟' => 'در پاسخ به اتساع دهلیز ترشح شده و دفع سدیم و آب از کلیه را افزایش می‌دهد.'],
        ];

        foreach ($cardioTerms as $cIdx => $item) {
            Flashcard::create([
                'flashcard_deck_id' => $deck1->id,
                'front' => array_key_first($item),
                'back' => reset($item),
                'sort_order' => $cIdx + 1,
                'is_free_designated' => $cIdx < 10, // Max free cap = 10
                'status' => 'published',
                'published_at' => now()->subDays(8),
            ]);
        }

        $quiz1 = Quiz::create([
            'course_id' => $course1->id,
            'title' => 'آزمون سنجش الکتروفیزیولوژی و مکانیک قلب',
            'slug' => 'cardio-physiology-quiz',
            'description' => 'آزمون تشخیصی چهارگزینه‌ای سنجش تسلط بر پتانسیل عمل و چرخه قلبی.',
            'sort_order' => 1,
            'pass_threshold_percent' => 70,
            'status' => 'published',
            'published_at' => now()->subDays(8),
            'author_id' => $drSara->id,
            'reviewer_id' => $drReza->id,
        ]);

        $quizQuestions = [
            [
                'پتانسیل استراحت غشایی در میوسیت‌های بطنی عمدتاً توسط کدام یون برقرار می‌گردد؟',
                ['یون پتاسیم (K+)', 'یون سدیم (Na+)', 'یون کلسیم (Ca2+)', 'یون کلر (Cl-)'],
                0,
                true, // free designated question (global cap = 1)
                'پتانسیل استراحت غشا نزدیک به پتانسیل تعادلی پتاسیم (حدود -۹۰ میلی‌ولت) است.',
            ],
            [
                'فاز کفه (Plateau) در پتانسیل عمل سلول‌های عضلانی قلب ناشی از جریان کدام یون است؟',
                ['ورود کلسیم از کانال‌های کند L-Type', 'خروج سریع پتاسیم', 'ورود ناگهانی سدیم', 'خروج منیزیم'],
                0,
                false,
                'ورود کلسیم از کانال‌های کند تیپ L همزمان با کاهش خروج پتاسیم فاز کفه را ایجاد می‌کند.',
            ],
            [
                'کدام رویداد بیانگر انتهای مرحله انقباض هم‌حجم (Isovolumetric Contraction) است؟',
                ['باز شدن دریچه آئورت و ریوی', 'بسته شدن دریچه میترال', 'باز شدن دریچه میترال', 'رسیدن فشار بطن به صفر'],
                0,
                false,
                'وقتی فشار بطن از فشار آئورت بیشتر شود، دریچه آئورت باز شده و مرحله تخلیه آغاز می‌شود.',
            ],
        ];

        foreach ($quizQuestions as $qIdx => [$prompt, $options, $corrIdx, $isFree, $exp]) {
            $q = QuizQuestion::create([
                'quiz_id' => $quiz1->id,
                'prompt' => $prompt,
                'explanation' => $exp,
                'source_citation' => 'فیزیولوژی پزشکی گایتون - هال / فصل ۹',
                'sort_order' => $qIdx + 1,
                'is_free_designated' => $isFree,
                'status' => 'published',
                'published_at' => now()->subDays(8),
                'author_id' => $drSara->id,
                'reviewer_id' => $drReza->id,
            ]);

            foreach ($options as $oIdx => $optLabel) {
                QuizOption::create([
                    'quiz_question_id' => $q->id,
                    'label' => $optLabel,
                    'is_correct' => $oIdx === $corrIdx,
                    'sort_order' => $oIdx + 1,
                ]);
            }
        }

        // COURSE 2: Comprehensive Neuroanatomy & Broca's Area
        $course2 = Course::create([
            'subject_id' => $subjNeuro->id,
            'title' => 'نوروآناتومی جامع: ساختار مغز و ناحیه بروکا',
            'slug' => 'neuroanatomy-and-broca-area',
            'excerpt' => 'بررسی تشریحی قشر مخ، لوب‌های فرونتال و تمپورال، مسیرهای زبانی و ناحیه بروکا.',
            'description' => 'این دوره آموزشی به بررسی کالبدشناسی سیستم عصبی مرکزی، کورتکس مغز، نواحی ۴۴ و ۴۵ برودمن (مرکز تکلم بروکا)، فاسیکولوس آرکوآت و تفاوت‌های بالینی آفازی بروکا و ورنیکه می‌پردازد.',
            'status' => 'published',
            'published_at' => now()->subDays(6),
            'author_id' => $drMaryam->id,
            'reviewer_id' => $drReza->id,
            'level' => 'تخصصی علوم اعصاب',
            'sort_order' => 2,
        ]);

        foreach ([
            ['ساختار قشر مخ، شیارها و لوب‌های مغزی', 'brain-cortex-structure', false, 890],
            ['ناحیه بروکا و مراکز پردازش و بیان تکلم', 'broca-speech-area', false, 950],
            ['مسیرهای هدایت عصبی کورتیکواسپاینال و حس‌های پیکری', 'neural-pathways', false, 1020],
        ] as $idx => [$vTitle, $vSlug, $isFree, $dur]) {
            Video::create([
                'course_id' => $course2->id,
                'title' => $vTitle,
                'slug' => $vSlug,
                'description' => 'درس ' . ($idx + 1) . ' از دوره نوروآناتومی جامع.',
                'sort_order' => $idx + 1,
                'duration_seconds' => $dur,
                'manifest_reference' => 'sample-video.mp4',
                'is_free_designated' => $isFree,
                'status' => 'published',
                'published_at' => now()->subDays(5),
                'author_id' => $drMaryam->id,
                'reviewer_id' => $drReza->id,
            ]);
        }

        Note::create([
            'course_id' => $course2->id,
            'title' => 'اطلس دیاگرام‌های قشر مخ و ناحیه بروکا (PDF)',
            'slug' => 'neuroanatomy-broca-atlas',
            'description' => 'نمودارهای رنگی لوب‌های مغزی، نواحی برودمن و مسیرهای زبانی.',
            'sort_order' => 1,
            'storage_disk' => 'local',
            'storage_key' => 'notes/neuroanatomy-broca-atlas.pdf',
            'mime_type' => 'application/pdf',
            'is_free_designated' => false,
            'status' => 'published',
            'published_at' => now()->subDays(5),
            'author_id' => $drMaryam->id,
            'reviewer_id' => $drReza->id,
        ]);

        $deck2 = FlashcardDeck::create([
            'course_id' => $course2->id,
            'title' => 'فلش‌کارت‌های نورولوژی و نواحی مغزی',
            'slug' => 'neuroanatomy-flashcards',
            'description' => 'مرور نواحی برودمن، هسته‌های تالاموس و علائم آفازی‌های حرکتی و حسی.',
            'sort_order' => 1,
            'status' => 'published',
            'published_at' => now()->subDays(5),
            'author_id' => $drMaryam->id,
            'reviewer_id' => $drReza->id,
        ]);

        $neuroTerms = [
            ['ناحیه بروکا (Broca Area) در کدام لوب قرار دارد و شامل چه مناطقی است؟' => 'در شکنج تحتانی لوب فرونتال (Pars Opercularis و Pars Triangularis) و منطبق بر نواحی ۴۴ و ۴۵ برودمن.'],
            ['آفازی بروکا (Expressive Aphasia) چه علامتی دارد؟' => 'اشکال در تولید و بیان روان کلمات در حالی که درک زبان تا حد زیادی حفظ شده است.'],
            ['دسته فیبرهای ارتباطی بین ناحیه ورنیکه و بروکا چه نام دارد؟' => 'دسته کمانی یا فاسیکولوس آرکوآت (Arcuate Fasciculus).'],
            ['قشر بینایی اولیه (V1) در کدام لوب قرار دارد؟' => 'در لوب پس‌سری (Occipital) در دو طرف شیار کالکارین (ناحیه ۱۷ برودمن).'],
            ['مرکز کنترل حرکتی اولیه (M1) کجاست؟' => 'در شکنج پیش‌مرکزی (Precentral Gyrus) لوب فرونتال (ناحیه ۴ برودمن).'],
        ];

        foreach ($neuroTerms as $cIdx => $item) {
            Flashcard::create([
                'flashcard_deck_id' => $deck2->id,
                'front' => array_key_first($item),
                'back' => reset($item),
                'sort_order' => $cIdx + 1,
                'is_free_designated' => false,
                'status' => 'published',
                'published_at' => now()->subDays(5),
            ]);
        }

        // COURSE 3: Clinical Anatomy of Thorax & Musculoskeletal System
        $course3 = Course::create([
            'subject_id' => $subjAnatomy->id,
            'title' => 'آناتومی بالینی قفسه سینه و سیستم اسکلتی-عضلانی',
            'slug' => 'clinical-anatomy-thorax-musculoskeletal',
            'excerpt' => 'کالبدشناسی کاربردی اسکلت قفسه سینه، عضلات تنفسی، مفاصل و مجاورات عروقی-عصبی.',
            'description' => 'این دوره ساختار دنده‌ها، جناغ سینه، مهره‌های توراسیک، عضلات بین‌دنده‌ای و دیافراگم، و همچنین آناتومی کاربردی اندام فوقانی و دست را بر اساس استانداردهای جراحی و بالینی آموزش می‌دهد.',
            'status' => 'published',
            'published_at' => now()->subDays(4),
            'author_id' => $drNima->id,
            'reviewer_id' => $drReza->id,
            'level' => 'کالبدشناسی کاربردی',
            'sort_order' => 3,
        ]);

        foreach ([
            ['ساختار اسکلت قفسه سینه، دنده‌ها و مفاصل مهره‌ای', 'thorax-bony-skeleton', false, 790],
            ['عضلات بین‌دنده‌ای، مکانیک تنفس و دیافراگم', 'respiratory-muscles-diaphragm', false, 860],
            ['آناتومی کاربردی اندام فوقانی و شبکه‌های عصبی', 'upper-limb-anatomy', false, 940],
        ] as $idx => [$vTitle, $vSlug, $isFree, $dur]) {
            Video::create([
                'course_id' => $course3->id,
                'title' => $vTitle,
                'slug' => $vSlug,
                'description' => 'درس ' . ($idx + 1) . ' از دوره آناتومی قفسه سینه.',
                'sort_order' => $idx + 1,
                'duration_seconds' => $dur,
                'manifest_reference' => 'sample-video.mp4',
                'is_free_designated' => $isFree,
                'status' => 'published',
                'published_at' => now()->subDays(3),
                'author_id' => $drNima->id,
                'reviewer_id' => $drReza->id,
            ]);
        }

        Note::create([
            'course_id' => $course3->id,
            'title' => 'راهنمای تشریح و نکات بالینی توراکس و مفاصل (PDF)',
            'slug' => 'thorax-clinical-guide',
            'description' => 'نکات تشریحی و لندمارک‌های مهم بالینی توراکوسنتز و جراحی‌های قفسه سینه.',
            'sort_order' => 1,
            'storage_disk' => 'local',
            'storage_key' => 'notes/thorax-clinical-guide.pdf',
            'mime_type' => 'application/pdf',
            'is_free_designated' => false,
            'status' => 'published',
            'published_at' => now()->subDays(3),
            'author_id' => $drNima->id,
            'reviewer_id' => $drReza->id,
        ]);

        $this->seedBlogPosts();

        // Student automatically enrolled in Course 1 for immediate testing
        $student->enrollments()->create([
            'course_id' => $course1->id,
            'enrolled_at' => now()->subDays(2),
            'status' => 'active',
        ]);
    }

    private function seedPlans(): void
    {
        $plans = [
            [
                'code' => 'free',
                'name' => 'پلن پایه رایگان',
                // Global cap wording — mirrors EntitlementService constants;
                // the quota is shared across the whole archive, not per course.
                'description' => 'تا ۲ ویدیوی منتخب، ۱ جزوه، ۱۰ فلش‌کارت و ۱ سؤال آزمون در کل آرشیو — برای ارزیابی پیش از خرید.',
                'duration_months' => 0,
                'price_irr' => 0,
                'sort_order' => 1,
            ],
            [
                'code' => 'monthly',
                'name' => 'اشتراک یک‌ماهه طلایی',
                'description' => 'دسترسی نامحدود به تمام ویدیوهای بالینی، جزوات اختصاصی، آزمون‌ها و سیستم هوشمند SRS برای ۳۰ روز.',
                'duration_months' => 1,
                'price_irr' => 2700, // 270 Toman
                'sort_order' => 2,
            ],
            [
                'code' => 'quarterly',
                'name' => 'اشتراک سه‌ماهه جامع',
                'description' => 'دسترسی کامل به کل آرشیو دوره‌ها، آزمون‌های جامع و دسته‌های فلش‌کارت برای ۹۰ روز با تخفیف ویژه.',
                'duration_months' => 3,
                'price_irr' => 6000, // 600 Toman
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $p) {
            Plan::updateOrCreate(['code' => $p['code']], $p + ['is_active' => true]);
        }
    }

    private function seedBlogPosts(): void
    {
        BlogPost::updateOrCreate(
            ['slug' => 'broca-area-and-aphasia'],
            [
                'title' => 'کالبدشناسی ناحیه بروکا و مقایسه بالینی آفازی‌های حرکتی و حسی',
                'category' => 'نورولوژی و علوم اعصاب',
                'author_name' => 'دکتر مریم حسینی',
                'reviewer_name' => 'دکتر رضا کریمی',
                'excerpt' => 'مروری کاربردی بر جایگاه ناحیه بروکا، تفاوت آفازی بروکا و ورنیکه و اهمیت بالینی آن در نورولوژی.',
                'content' => "ناحیه بروکا یکی از کلیدی‌ترین مراکز تولید گفتار در قشر مخ است. این ناحیه در لوب فرونتال نیمکره غالب قرار دارد و در سازمان‌دهی گفتار، تولید کلمات و ساختار دستوری جمله نقش مهمی ایفا می‌کند.\n\nاز نظر بالینی، آسیب این ناحیه معمولاً با آفازی بروکا همراه است؛ بیمار معنی را تا حد زیادی می‌فهمد اما بیان گفتار او کند، شکسته و با تلاش زیاد است.\n\nدر مقابل، در آفازی ورنیکه روانی گفتار حفظ می‌شود اما محتوای کلام بی‌معنا یا نامرتبط می‌گردد. تمایز این دو الگو برای دانشجویان پزشکی و کارورزان نورولوژی اهمیت بالایی دارد.\n\nدر بروکا تلاش می‌کنیم این مفاهیم را با ویدیو، فلش‌کارت و آزمون‌های بالینی ساده‌تر و ماندگارتر کنیم.",
                'status' => 'published',
                'published_at' => now()->subDays(2),
                'meta_title' => 'ناحیه بروکا و آفازی‌ها',
                'meta_description' => 'مقاله آموزشی فارسی درباره ناحیه بروکا، آفازی بروکا و ورنیکه و نکات بالینی مرتبط.',
            ],
        );
    }

    /**
     * Copy the committed placeholder video/note files into the runtime
     * locations (public/videos, storage/app/private/notes) so seeded
     * manifest_reference / storage_key values resolve instead of 404ing.
     * Run `php artisan broca:provision-media` again after swapping in the
     * real assets.
     */
    private function provisionPlaceholderMedia(): void
    {
        \Illuminate\Support\Facades\Artisan::call('broca:provision-media');
    }
}
