<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserFlashcardSchedule extends Model
{
    protected $fillable = ['user_id', 'flashcard_id', 'state', 'ease_factor', 'interval_days', 'repetition_count', 'due_at', 'last_reviewed_at'];

    protected $table = 'user_flashcard_schedules';

    protected function casts(): array
    {
        return ['ease_factor' => 'float', 'due_at' => 'datetime', 'last_reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function flashcard(): BelongsTo { return $this->belongsTo(Flashcard::class); }
    public function reviews(): HasMany { return $this->hasMany(FlashcardReview::class, 'schedule_id'); }
}
