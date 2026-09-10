<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Support\PhoneNormalizer;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Security: `is_admin` and `status` are deliberately NOT mass-assignable.
 * They are only set through forceFill() in factories/tests and explicit
 * admin tooling, so no request payload can ever grant admin or flip status.
 *
 * Email routing: `sendEmailVerificationNotification()` and
 * `sendPasswordResetNotification()` are overridden below so verification and
 * password-reset mail use the app's own Persian, queued notifications
 * (VerifyEmailNotification / ResetPasswordNotification) rather than the
 * framework's default English, synchronous ones.
 */
#[Fillable(['name', 'email', 'phone', 'password'])]
// `phone_verification_code` is a bcrypt hash, but it is a live credential for
// as long as it is unexpired — keeping it out of array/JSON output stops it
// leaking through an admin export or a careless `toArray()` in a log line.
#[Hidden(['password', 'remember_token', 'totp_secret', 'recovery_codes', 'phone_verification_code'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var array<int, bool> */
    protected array $enrollmentCache = [];

    protected ?Subscription $activeSubscriptionCache = null;

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(UserConsent::class);
    }

    public function flashcardSchedules(): HasMany
    {
        return $this->hasMany(UserFlashcardSchedule::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function videoProgress(): HasMany
    {
        return $this->hasMany(VideoProgress::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * The account is usable once EITHER contact channel is proven: the
     * emailed link or the SMS code. Signup must not have a single point of
     * failure — see App\Http\Middleware\EnsureVerifiedContact.
     */
    public function hasVerifiedContact(): bool
    {
        return $this->hasVerifiedEmail() || $this->hasVerifiedPhone();
    }

    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function markPhoneAsVerified(): void
    {
        $this->forceFill([
            'phone_verified_at' => now(),
            'phone_verification_code' => null,
            'phone_verification_expires_at' => null,
            'phone_verification_attempts' => 0,
        ])->save();
    }

    /**
     * Where text messages for this account go.
     *
     * `phone` is already canonical (`09…`) — registration and
     * broca:identifiers:normalize both write it that way — but it is
     * re-normalized here so an account imported by hand, or written before
     * normalization existed, still receives its code.
     */
    public function routeNotificationForSms(): ?string
    {
        if (! is_string($this->phone) || trim($this->phone) === '') {
            return null;
        }

        try {
            return PhoneNormalizer::normalize($this->phone);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * The verification mail must use the app's Persian, queued notification
     * (VerifyEmailNotification) — the inherited MustVerifyEmail trait would
     * otherwise send the framework's stock, synchronous, English email.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * Same for password-reset mail: route through the app's Persian, queued
     * ResetPasswordNotification instead of the framework default.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function hasConfirmedTwoFactor(): bool
    {
        return ! empty($this->totp_secret) && ! empty($this->totp_confirmed_at);
    }

    /**
     * @return list<string> The stored (sha256-hashed) recovery codes.
     *                      Plaintext codes are shown once at creation and
     *                      never persisted — a DB leak must not bypass 2FA.
     */
    public function recoveryCodes(): array
    {
        return is_array($this->recovery_codes) ? $this->recovery_codes : [];
    }

    /** @param list<string> $codes Plaintext codes; only their bcrypt hashes are stored. */
    public function storeRecoveryCodes(array $codes): void
    {
        // bcrypt (not sha256): recovery codes are short bearer secrets and
        // sha256 of a 10-char code is GPU-bruteforceable in minutes after
        // a DB leak. 10 codes per admin makes bcrypt cost negligible.
        // Codes stored before this change no longer validate — admins
        // regenerate them via the recovery-codes endpoint.
        $hashed = array_map(
            fn (string $code): string => Hash::make(strtoupper(trim($code))),
            $codes
        );

        $this->forceFill(['recovery_codes' => array_values($hashed)])->save();
    }

    public function consumeRecoveryCode(string $code): bool
    {
        $normalized = strtoupper(trim($code));

        return DB::transaction(function () use ($normalized): bool {
            $user = self::query()->whereKey($this->getKey())->lockForUpdate()->first();
            $codes = $user?->recoveryCodes() ?? [];

            foreach ($codes as $index => $stored) {
                if (is_string($stored) && Hash::check($normalized, $stored)) {
                    unset($codes[$index]);
                    $user->forceFill(['recovery_codes' => array_values($codes)])->save();

                    // The transaction saves on a locked copy — sync this
                    // instance so a later read in the same request never
                    // sees the consumed code as still available.
                    $this->recovery_codes = $user->recovery_codes;

                    return true;
                }
            }

            return false;
        });
    }

    /**
     * The currently active subscription (memoized per instance), or null.
     * Single source of truth for "does this user currently have a paid,
     * activated, non-expired subscription" — every policy, gate, and view
     * helper must call this (or hasActiveSubscription) — never re-implement
     * the expiry logic. A NULL ends_at means the subscription never expires.
     */
    public function activeSubscription(): ?Subscription
    {
        return $this->activeSubscriptionCache ??= $this->subscriptions()
            ->where('status', 'active')
            ->whereNotNull('activated_at')
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', now())
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->with('plan')
            ->latest('id')
            ->first();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    /**
     * Memoized active-enrollment check for a course.
     */
    public function isEnrolledIn(int $courseId): bool
    {
        return $this->enrollmentCache[$courseId] ??= $this->enrollments()
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'phone_verification_expires_at' => 'datetime',
            'phone_verification_last_sent_at' => 'datetime',
            'phone_verification_attempts' => 'integer',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'totp_confirmed_at' => 'datetime',
            // Encrypted at rest: the TOTP seed is a bearer secret.
            'totp_secret' => 'encrypted',
            'recovery_codes' => 'array',
        ];
    }
}
