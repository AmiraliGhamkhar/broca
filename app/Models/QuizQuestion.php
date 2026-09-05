<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['quiz_id', 'prompt', 'explanation', 'source_citation', 'sort_order', 'status', 'published_at', 'author_id', 'reviewer_id', 'is_free_designated'])]
class QuizQuestion extends Model
{
    use HasFactory;

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

    public function quiz(): BelongsTo { return $this->belongsTo(Quiz::class); }
    public function options(): HasMany { return $this->hasMany(QuizOption::class); }
}
