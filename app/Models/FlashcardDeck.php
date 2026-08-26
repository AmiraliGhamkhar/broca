<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'title', 'slug', 'description', 'sort_order', 'status', 'published_at', 'author_id', 'reviewer_id'])]
class FlashcardDeck extends Model
{
    use SoftDeletes;
    protected function casts(): array { return ['published_at' => 'datetime']; }
    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
    public function cards(): HasMany { return $this->hasMany(Flashcard::class); }
}
