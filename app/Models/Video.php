<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
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
#[Hidden([])]
class Video extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
