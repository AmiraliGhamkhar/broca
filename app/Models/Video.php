<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'course_id',
    'title',
    'slug',
    'description',
    'sort_order',
    'duration_seconds',
    'playback_provider',
    'playback_asset_id',
    'manifest_reference',
    'completion_threshold_percent',
    'is_free_designated',
    'status',
    'published_at',
    'author_id',
    'reviewer_id',
])]
class Video extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    /**
     * published_at MUST be cast to datetime: gating code calls
     * `$video->published_at?->isPast()`, which is fatal on a raw string.
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_free_designated' => 'boolean',
            'duration_seconds' => 'integer',
            'completion_threshold_percent' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Contributor::class, 'author_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Contributor::class, 'reviewer_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && $this->published_at?->isPast();
    }
}
