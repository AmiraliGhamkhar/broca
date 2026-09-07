<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\PasswordPolicy;
use App\Support\PhoneNormalizer;
use App\Support\UserLookup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * `php artisan broca:user:repair {email|mobile|id} [options]`
 *
 * The recovery tool for "nobody can sign in" — including the case that
 * started this: the only administrator locked out of their own site with
 * credentials that are, as far as they know, correct.
 *
 * It is deliberately a CLI. You cannot fix a broken login from inside a
 * session that requires the login to work, and on shared hosting there is
 * often no database client — but there is always SSH or cPanel's terminal.
 *
 * What each flag does:
 *   --normalize      rewrite email/phone into the canonical spelling login
 *                    compares against (lowercased/trimmed, `09…`)
 *   --activate       set status=active (a suspended account is refused even
 *                    with the right password)
 *   --verify-email   mark the email verified (skips the post-login gate)
 *   --verify-phone   mark the mobile verified
 *   --promote        grant is_admin
 *   --demote         revoke is_admin (refused for the last active admin)
 *   --password=…     set a new password (validated against the app policy)
 *   --logout         drop the account's stored sessions (force re-login)
 *
 * With no flags, `--activate --normalize --verify-email` is assumed — the
 * three things that restore access without changing credentials. Every write
 * is confirmed first unless --force.
 */
class RepairUser extends Command
{
    protected $signature = 'broca:user:repair
                            {identifier : Email, mobile number, or numeric user id}
                            {--password= : New password (omit the value to be prompted securely)}
                            {--activate}
                            {--verify-email}
                            {--verify-phone}
                            {--promote}
                            {--demote}
                            {--normalize}
                            {--logout}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Restore login access to an account (status, canonical identifiers, verification, admin role, password)';

    public function handle(): int
    {
        $identifier = trim((string) $this->argument('identifier'));

        $user = ctype_digit($identifier)
            ? User::query()->find((int) $identifier)
            : $this->lookup($identifier);

        if (! $user) {
            $this->error('هیچ حسابی با این شناسه پیدا نشد: '.$identifier);
            $this->line('برای بررسی بیشتر: php artisan broca:user:diagnose "'.$identifier.'"');

            return self::FAILURE;
        }

        $explicit = array_filter([
            'activate' => (bool) $this->option('activate'),
            'verify-email' => (bool) $this->option('verify-email'),
            'verify-phone' => (bool) $this->option('verify-phone'),
            'promote' => (bool) $this->option('promote'),
            'demote' => (bool) $this->option('demote'),
            'normalize' => (bool) $this->option('normalize'),
            'logout' => (bool) $this->option('logout'),
            'password' => $this->option('password') !== null,
        ]);

        // No flags = "make this account able to log in", without touching the
        // password or the admin role.
        $plan = $explicit === []
            ? ['activate' => true, 'normalize' => true, 'verify-email' => true]
            : $explicit;

        $newPassword = $this->resolvePassword();

        // A password was asked for but did not survive validation: stop rather
        // than silently leaving the account with the old (unusable) one.
        if (($plan['password'] ?? false) && $newPassword === null) {
            $this->error('گذرواژه تعیین نشد؛ هیچ تغییری اعمال نشد.');

            return self::FAILURE;
        }

        if (! $this->confirmPlan($user, $plan, $newPassword)) {
            $this->line('لغو شد؛ هیچ تغییری اعمال نشد.');

            return self::SUCCESS;
        }

        $changes = $this->apply($user, $plan, $newPassword);

        if ($changes === []) {
            $this->line('تغییری لازم نبود؛ حساب در وضعیت سالم است.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('تغییرات اعمال‌شده روی #'.$user->getKey().' ('.$user->email.'):');
        foreach ($changes as $change) {
            $this->line('  • '.$change);
        }

        $this->newLine();
        $this->line('بررسی دوباره: php artisan broca:user:diagnose '.$user->getKey());

        return self::SUCCESS;
    }

    /**
     * @param  array<string, bool>  $plan
     * @return list<string>
     */
    private function apply(User $user, array $plan, ?string $newPassword): array
    {
        $changes = [];

        if (($plan['normalize'] ?? false)) {
            $email = mb_strtolower(trim((string) $user->email));
            if ($email !== $user->email) {
                $changes[] = 'ایمیل به شکل استاندارد بازنویسی شد: '.$email;
                $user->forceFill(['email' => $email]);
            }

            $phone = trim((string) $user->phone);
            if ($phone !== '') {
                try {
                    $canonical = PhoneNormalizer::normalize($phone);
                    if ($canonical !== $phone) {
                        $changes[] = 'موبایل به شکل استاندارد بازنویسی شد: '.$canonical;
                        $user->forceFill(['phone' => $canonical]);
                    }
                } catch (\InvalidArgumentException) {
                    $this->warn('شمارهٔ موبایل قابل تبدیل به قالب استاندارد نیست و بدون تغییر ماند: '.$phone);
                }
            }
        }

        if (($plan['activate'] ?? false) && ! $user->isActive()) {
            $changes[] = 'وضعیت از «'.$user->status.'» به «active» تغییر کرد';
            $user->forceFill(['status' => 'active']);
        }

        if (($plan['verify-email'] ?? false) && ! $user->hasVerifiedEmail()) {
            $changes[] = 'ایمیل تأیید شد';
            $user->forceFill(['email_verified_at' => $user->email_verified_at ?: now()]);
        }

        if (($plan['verify-phone'] ?? false) && ! $user->hasVerifiedPhone()) {
            if (trim((string) $user->phone) === '') {
                $this->warn('حساب شمارهٔ موبایلی ندارد؛ تأیید موبایل اعمال نشد.');
            } else {
                $changes[] = 'موبایل تأیید شد';
                $user->forceFill(['phone_verified_at' => $user->phone_verified_at ?: now()]);
            }
        }

        if (($plan['promote'] ?? false) && ! $user->is_admin) {
            $changes[] = 'نقش مدیر اعطا شد';
            $user->forceFill(['is_admin' => true]);
        }

        if (($plan['demote'] ?? false) && $user->is_admin) {
            $otherAdmins = User::query()
                ->where('is_admin', true)
                ->where('status', 'active')
                ->whereKeyNot($user->getKey())
                ->count();

            // Absolute guard, not a confirmation: --force skips prompts, it
            // does not license bricking the site. Demoting the last active
            // administrator leaves /admin unreachable by everyone, which is
            // the outage this command exists to end, not to cause.
            if ($otherAdmins === 0) {
                $this->error('این آخرین مدیر فعال سیستم است؛ سلب نقش باعث قفل شدن کامل پنل مدیریت می‌شود.');

                return $changes;
            }

            $changes[] = 'نقش مدیر سلب شد';
            $user->forceFill(['is_admin' => false]);
        }

        if ($newPassword !== null) {
            $changes[] = 'گذرواژه بازنویسی شد';
            // The 'hashed' cast hashes the plain value; nothing else in the app
            // needs to know how.
            $user->forceFill(['password' => $newPassword]);
        }

        $user->save();

        if (($plan['logout'] ?? false)) {
            // Mirrors Admin\UserController: suspension and password recovery
            // must bite immediately, not at the next request.
            try {
                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $user->getKey())
                    ->delete();
                $changes[] = 'نشست‌های ذخیره‌شده حذف شدند';
            } catch (\Throwable) {
                $this->warn('جدول نشست‌ها در دسترس نبود؛ نشست‌ها حذف نشدند.');
            }
        }

        return $changes;
    }

    private function resolvePassword(): ?string
    {
        if ($this->option('password') === null) {
            return null;
        }

        $password = (string) $this->option('password');

        if ($password === '') {
            $password = (string) $this->secret('گذرواژهٔ جدید');
            $password = trim($password);

            if ($password === '') {
                $this->error('گذرواژه نمی‌تواند خالی باشد.');

                return null;
            }

            if ($password !== (string) $this->secret('تکرار گذرواژهٔ جدید')) {
                $this->error('دو مقدار یکسان نیستند.');

                return null;
            }
        }

        $validator = Validator::make(
            ['password' => $password],
            ['password' => ['required', 'string', PasswordPolicy::maxRule(), PasswordPolicy::rule()]]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            $this->line('راهنما: '.PasswordPolicy::hint());

            return null;
        }

        return $password;
    }

    /**
     * @param  array<string, bool>  $plan
     */
    private function confirmPlan(User $user, array $plan, ?string $newPassword): bool
    {
        if ((bool) $this->option('force')) {
            return true;
        }

        $this->line('حساب: #'.$user->getKey().'  '.$user->name.'  <'.$user->email.'>  ('.$user->status.')');
        $this->newLine();
        $this->line('تغییراتی که اعمال می‌شود:');

        $descriptions = [
            'normalize' => 'بازنویسی ایمیل/موبایل به قالب استاندارد',
            'activate' => 'فعال‌سازی حساب (status=active)',
            'verify-email' => 'تأیید ایمیل',
            'verify-phone' => 'تأیید شمارهٔ موبایل',
            'promote' => 'اعطای نقش مدیر',
            'demote' => 'سلب نقش مدیر',
            'logout' => 'حذف نشست‌های ذخیره‌شده',
            'password' => 'تعیین گذرواژهٔ جدید',
        ];

        foreach ($descriptions as $key => $description) {
            if (($plan[$key] ?? false) && ($key !== 'password' || $newPassword !== null)) {
                $this->line('  • '.$description);
            }
        }

        return $this->confirm('ادامه می‌دهید؟', true);
    }

    private function lookup(string $identifier): ?User
    {
        try {
            return UserLookup::find($identifier);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
