<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\PhoneNormalizer;
use Illuminate\Console\Command;

/**
 * `php artisan broca:identifiers:normalize`
 *
 * Rewrites every stored email and mobile number into the canonical spelling
 * the login form compares against.
 *
 * This repairs the class of lockout that looks like a wrong password but is
 * not: rows written by an import, by hand-run SQL during a migration, or
 * before the normalizers existed ("Admin@Example.com ", "+98912…") are
 * invisible to a byte-for-byte lookup, so the account exists, the password is
 * right, and the form still says «ایمیل/شمارهٔ همراه یا گذرواژه درست نیست».
 *
 * Login tolerates those spellings (App\Support\UserLookup) so nobody is locked
 * out in the meantime; this command makes the data canonical so the fast,
 * indexed path is the one that hits.
 */
class NormalizeUserIdentifiers extends Command
{
    protected $signature = 'broca:identifiers:normalize {--dry-run : Report what would change without writing}';

    protected $description = 'Rewrite stored emails and mobile numbers into the canonical form used for login lookup';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $emails = 0;
        $phones = 0;
        $skipped = [];

        User::query()->orderBy('id')->chunkById(200, function ($users) use (&$emails, &$phones, &$skipped, $dryRun): void {
            foreach ($users as $user) {
                $dirty = false;

                $email = mb_strtolower(trim((string) $user->email));
                if ($email !== '' && $email !== $user->email) {
                    $emails++;
                    $dirty = true;
                    $user->forceFill(['email' => $email]);
                }

                $phone = trim((string) $user->phone);
                if ($phone !== '') {
                    try {
                        $canonical = PhoneNormalizer::normalize($phone);
                        if ($canonical !== $phone) {
                            $phones++;
                            $dirty = true;
                            $user->forceFill(['phone' => $canonical]);
                        }
                    } catch (\InvalidArgumentException) {
                        // Not a number this app can canonicalize (an
                        // international mobile, or bad data). Leave it alone
                        // and report it — rewriting it would destroy the
                        // owner's only way to prove the account is theirs.
                        $skipped[] = '#'.$user->getKey().': '.$phone;
                    }
                }

                if ($dirty && ! $dryRun) {
                    $user->save();
                }
            }
        });

        $this->line(sprintf(
            '%s ایمیل اصلاح شد و %s شمارهٔ موبایل استاندارد شد%s.',
            $emails,
            $phones,
            $dryRun ? ' (اجرای آزمایشی؛ چیزی ذخیره نشد)' : ''
        ));

        if ($skipped !== []) {
            $this->newLine();
            $this->warn('مواردی که بدون تغییر ماندند (قالب قابل تشخیص نیست):');
            foreach (array_slice($skipped, 0, 20) as $entry) {
                $this->line('  • '.$entry);
            }
        }

        return self::SUCCESS;
    }
}
