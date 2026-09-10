<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashcardReview extends Model
{
    protected $fillable = ['user_id', 'flashcard_id', 'schedule_id', 'quality', 'previous_interval_days', 'new_interval_days', 'previous_ease_factor', 'new_ease_factor', 'reviewed_at'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function flashcard(): BelongsTo
    {
        return $this->belongsTo(Flashcard::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(UserFlashcardSchedule::class, 'schedule_id');
    }
}
