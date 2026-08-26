<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = ['invoice_id', 'user_id', 'plan_id', 'status', 'starts_at', 'ends_at', 'activated_at', 'gateway', 'gateway_reference'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at && $this->ends_at->isFuture();
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || ($this->ends_at && $this->ends_at->isPast());
    }

    public function activate(?Carbon $at = null): void
    {
        $this->update([
            'status' => 'active',
            'activated_at' => $at ?? now(),
            'starts_at' => $this->starts_at ?? ($at ?? now()),
        ]);
    }

    public function schedule(Carbon $startsAt, int $durationMonths = 1): void
    {
        $this->update([
            'status' => 'scheduled',
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMonths($durationMonths),
        ]);
    }

    public function expire(): void
    {
        $this->update(['status' => 'expired']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}
