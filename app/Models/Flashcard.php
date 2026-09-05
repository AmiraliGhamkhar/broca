<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['flashcard_deck_id', 'front', 'back', 'hint', 'sort_order', 'is_free_designated', 'status', 'published_at'])]
class Flashcard extends Model
{
    use SoftDeletes;
    protected function casts(): array { return ['published_at' => 'datetime', 'is_free_designated' => 'boolean']; }

    // Single source of truth for "published" (see Note/Course/Video scopes).
    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at?->isPast();
    }

    public function deck(): BelongsTo { return $this->belongsTo(FlashcardDeck::class, 'flashcard_deck_id'); }

    public function schedules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserFlashcardSchedule::class);
    }
}
