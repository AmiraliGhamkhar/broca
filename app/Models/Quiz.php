<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'title', 'slug', 'description', 'pass_threshold_percent', 'is_free_designated', 'status', 'published_at', 'author_id', 'reviewer_id'])]
class Quiz extends Model
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

    public function course(): BelongsTo { return $this->belongsTo(Course::class); }

    public function author(): BelongsTo { return $this->belongsTo(Contributor::class, 'author_id'); }

    public function reviewer(): BelongsTo { return $this->belongsTo(Contributor::class, 'reviewer_id'); }

    public function questions(): HasMany { return $this->hasMany(QuizQuestion::class); }
}
