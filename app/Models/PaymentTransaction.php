<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = ['invoice_id', 'gateway', 'request_payload', 'response_payload', 'reference_number', 'status', 'verified_at'];

    protected function casts(): array
    {
        return ['request_payload' => 'array', 'response_payload' => 'array', 'verified_at' => 'datetime'];
    }

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isInitiated(): bool
    {
        return $this->status === 'initiated';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markVerified(): void
    {
        $this->update(['status' => 'verified', 'verified_at' => now()]);
    }

    public function markFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}
