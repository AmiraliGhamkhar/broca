<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'title',
    'slug',
    'category',
    'author_name',
    'reviewer_name',
    'excerpt',
    'content',
    'cover_image_path',
    'status',
    'published_at',
    'meta_title',
    'meta_description',
])]
class BlogPost extends Model
{
    use HasFactory, SoftDeletes;

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at?->isPast();
    }
}
