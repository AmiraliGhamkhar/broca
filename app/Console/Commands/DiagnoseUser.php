<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\PhoneNormalizer;
use App\Support\UserLookup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * `php artisan broca:user:diagnose {email|mobile|id}`
 *
 * READ-ONLY. Answers, without guesswork: does this account exist, would the
 * login form find it, and — if it would not — exactly which of the four things
 * that block a login is in the way?
 *
 *  1. the row is not in the canonical spelling (case/padding, +98 prefix);
 *  2. `status` is not `active` (suspended — the form says "wrong password");
 *  3. the stored `password` is not a hash this app can verify (md5/sha1/plain
 *     left behind by an import — no correct password will ever work);
 *  4. the account has no verified contact channel, so the post-login gate
 *     bounces it to the verification page forever.
 *
 * Written for the operator who is locked out of their own site: it names the
 * repair command for whatever it finds.
 */
class DiagnoseUser extends Command
{
    protected $signature = 'broca:user:diagnose
                            {identifier : Email, mobile number, or numeric user id}
                            {--password= : Also check this password against the stored hash}';

    protected $description = 'Explain why an account can (or cannot) sign in — read-only';

    public function handle(): int
    {
        $identifier = trim((string) $this->argument('identifier'));

        $user = ctype_digit($identifier)
            ? User::query()->find((int) $identifier)
            : $this->lookup($identifier);

        if (! $user) {
            $this->error('هیچ حسابی با این شناسه پیدا نشد: '.$identifier);
            $this->showNearby($identifier);

            return self::FAILURE;
        }

        $problems = [];

        $this->table([], $this->rows($user, $problems));

        if ($problems === []) {
            $this->info('✅ مانعی برای ورود این حساب پیدا نشد.');
        } else {
            $this->newLine();
            $this->error('موارد نیازمند توجه:');
            foreach ($problems as $problem) {
                $this->line('  • '.$problem);
            }
        }

        return $problems === [] ? self::SUCCESS : self::FAILURE;
    }

    /** @return list<array{string, string}> */
    private function rows(User $user, array &$problems): array
    {
        $email = (string) $user->email;
        $emailCanonical = mb_strtolower(trim($email)) === $email;

        $phone = (string) $user->phone;
        $phoneCanonical = $phone === '' || $this->isCanonicalPhone($phone);

        $passwordAlgo = $this->hashAlgo((string) $user->password);
        $passwordVerifiable = ! str_contains($passwordAlgo, 'cannot be verified') && $passwordAlgo !== 'empty';

        $rows = [
            ['شناسه', (string) $user->getKey()],
            ['نام', (string) $user->name],
            ['ایمیل', $email.($emailCanonical ? '' : '   ← ذخیره‌شده با املای غیر استاندارد')],
            ['موبایل', ($phone === '' ? '—' : $phone).($phoneCanonical ? '' : '   ← ذخیره‌شده با املای غیر استاندارد')],
            ['وضعیت', (string) $user->status],
            ['نقش مدیر', $user->is_admin ? 'بله' : 'خیر'],
            ['تأیید ایمیل', $user->hasVerifiedEmail() ? (string) $user->email_verified_at : 'خیر'],
            ['تأیید موبایل', $user->hasVerifiedPhone() ? (string) $user->phone_verified_at : 'خیر'],
            ['الگوریتم گذرواژه', $passwordAlgo],
            ['ورود دومرحله‌ای', $user->hasConfirmedTwoFactor() ? 'فعال' : 'غیرفعال'],
            ['نشست‌های زنده', (string) $this->sessionCount($user)],
            ['اشتراک فعال', $user->hasActiveSubscription() ? 'بله' : 'خیر'],
            ['ساخته‌شده', (string) $user->created_at],
        ];

        if (! $emailCanonical) {
            $problems[] = 'ایمیل با حروف بزرگ یا فاصلهٔ اضافی ذخیره شده است. اصلاح: php artisan broca:user:repair '
                .$user->getKey().' --normalize';
        }

        if (! $phoneCanonical) {
            $problems[] = 'شمارهٔ موبایل در قالب استاندارد (09…) نیست. اصلاح: php artisan broca:user:repair '
                .$user->getKey().' --normalize';
        }

        if (! $user->isActive()) {
            $problems[] = 'وضعیت حساب «'.$user->status.'» است؛ فرم ورود حتی با گذرواژهٔ درست آن را رد می‌کند. '
                .'اصلاح: php artisan broca:user:repair '.$user->getKey().' --activate';
        }

        if (! $passwordVerifiable) {
            $problems[] = 'ستون گذرواژه یک هش قابل بررسی (bcrypt/argon) نیست؛ هیچ گذرواژهٔ درستی پذیرفته نمی‌شود. '
                .'اصلاح: php artisan broca:user:repair '.$user->getKey().' --password="گذرواژهٔ جدید"';
        }

        if (! $user->hasVerifiedContact()) {
            $problems[] = 'هیچ راه ارتباطی تأیید نشده است؛ بعد از ورود، صفحهٔ تأیید باز می‌شود و اشتراک/خرید در دسترس نیست. '
                .'اصلاح: php artisan broca:user:repair '.$user->getKey().' --verify-email';
        }

        if ($user->is_admin) {
            $otherActiveAdmins = User::query()
                ->where('is_admin', true)
                ->where('status', 'active')
                ->whereKeyNot($user->getKey())
                ->count();

            $rows[] = ['دیگر مدیران فعال', (string) $otherActiveAdmins];

            if (! $user->isActive() && $otherActiveAdmins === 0) {
                $problems[] = 'این تنها مدیر سیستم است و غیرفعال است؛ هیچ‌کس نمی‌تواند وارد /admin شود. '
                    .'همین حالا اجرا کنید: php artisan broca:user:repair '.$user->getKey().' --activate --promote';
            }
        }

        $candidate = $this->option('password');
        if (is_string($candidate) && $candidate !== '') {
            $matches = $passwordVerifiable && Hash::check($candidate, (string) $user->password);
            $rows[] = ['بررسی گذرواژهٔ ورودی', $matches ? 'درست است ✅' : 'نادرست است ❌'];
        }

        return $rows;
    }

    private function lookup(string $identifier): ?User
    {
        try {
            return UserLookup::find($identifier);
        } catch (\InvalidArgumentException) {
            // Not an email and not a phone: fall back to a loose search below.
            return null;
        }
    }

    /**
     * A near-miss spelling ("Admin@Example.com ") is the most common cause of
     * "my password is wrong", so when the exact lookup fails, look around.
     */
    private function showNearby(string $identifier): void
    {
        $term = '%'.addcslashes($identifier, '\\%_').'%';

        $candidates = User::query()
            ->where('name', 'like', $term)
            ->orWhere('email', 'like', $term)
            ->orWhere('phone', 'like', $term)
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone', 'status']);

        if ($candidates->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->line('حساب‌های نزدیک به این عبارت:');

        foreach ($candidates as $candidate) {
            $this->line(sprintf(
                '  #%d  %s  |  %s  |  %s  |  %s',
                $candidate->id,
                $candidate->name,
                $candidate->email,
                $candidate->phone ?: '—',
                $candidate->status
            ));
        }
    }

    private function isCanonicalPhone(string $phone): bool
    {
        try {
            return PhoneNormalizer::normalize($phone) === $phone;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    private function hashAlgo(string $stored): string
    {
        if ($stored === '') {
            return 'empty';
        }

        if (preg_match('/^\$(2[axyb]|argon2(id|i)?)\$/', $stored, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/^[a-f0-9]{32}$/i', $stored) === 1) {
            return 'md5 (legacy, cannot be verified)';
        }

        if (preg_match('/^[a-f0-9]{40}$/i', $stored) === 1) {
            return 'sha1 (legacy, cannot be verified)';
        }

        return 'unknown/plaintext (cannot be verified)';
    }

    private function sessionCount(User $user): int
    {
        try {
            return DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->count();
        } catch (\Throwable) {
            // Non-database session driver: nothing to count.
            return 0;
        }
    }
}
