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
        // Mirrors User::activeSubscription(): a NULL ends_at means the
        // subscription never expires, so it counts as active.
        return $this->status === 'active'
            && $this->activated_at !== null
            && $this->starts_at !== null
            && ! $this->starts_at->isFuture()
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function activate(?Carbon $at = null): void
    {
        $this->update([
            'status' => 'active',
            'activated_at' => $at ?? now(),
            'starts_at' => $this->starts_at ?? ($at ?? now()),
        ]);
    }
}
