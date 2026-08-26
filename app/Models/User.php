<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Security: `is_admin` and `status` are deliberately NOT mass-assignable.
 * They are only set through forceFill() in factories/tests and explicit
 * admin tooling, so no request payload can ever grant admin or flip status.
 */
#[Fillable(['name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token', 'totp_secret', 'recovery_codes'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var array<int, bool> */
    protected array $enrollmentCache = [];

    protected ?bool $activeSubscriptionCache = null;

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

    public function hasConfirmedTwoFactor(): bool
    {
        return ! empty($this->totp_secret) && ! empty($this->totp_confirmed_at);
    }

    /** @return list<string> */
    public function recoveryCodes(): array
    {
        return is_array($this->recovery_codes) ? $this->recovery_codes : [];
    }

    /** @param list<string> $codes */
    public function storeRecoveryCodes(array $codes): void
    {
        $this->forceFill(['recovery_codes' => array_values(array_map('strtoupper', $codes))])->save();
    }

    public function consumeRecoveryCode(string $code): bool
    {
        $normalized = strtoupper(trim($code));

        return DB::transaction(function () use ($normalized): bool {
            $user = self::query()->whereKey($this->getKey())->lockForUpdate()->first();
            $codes = $user?->recoveryCodes() ?? [];

            foreach ($codes as $index => $stored) {
                if (hash_equals((string) $stored, $normalized)) {
                    unset($codes[$index]);
                    $user->forceFill(['recovery_codes' => array_values($codes)])->save();

                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Single source of truth for "does this user currently have a paid,
     * activated, non-expired subscription". Every policy, gate, and view
     * helper must call this — never re-implement the expiry logic.
     *
     * Memoized per instance, so listing pages that check many items do not
     * re-run the query per item.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscriptionCache ??= $this->subscriptions()
            ->where('status', 'active')
            ->whereNotNull('activated_at')
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', now())
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->exists();
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
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'totp_confirmed_at' => 'datetime',
            'recovery_codes' => 'array',
        ];
    }
}
