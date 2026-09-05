<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['course_id', 'title', 'slug', 'description', 'sort_order', 'storage_disk', 'storage_key', 'mime_type', 'size_bytes', 'checksum', 'is_free_designated', 'status', 'published_at', 'author_id', 'reviewer_id'])]
class Note extends Model
{
    use SoftDeletes;
    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'is_free_designated' => 'boolean'];
    }

    // Single source of truth for "published" — same shape as the Video,
    // Course and BlogPost scopes. Callers must use this (or isPublished())
    // instead of re-implementing the status + date check inline.
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
}
