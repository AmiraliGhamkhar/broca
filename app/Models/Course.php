<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['subject_id', 'title', 'slug', 'excerpt', 'description', 'cover_image_path', 'status', 'published_at', 'author_id', 'reviewer_id', 'level', 'sort_order'])]
class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $course): void {
            if ($course->author_id && $course->reviewer_id && $course->author_id === $course->reviewer_id) {
                throw new \InvalidArgumentException('نویسنده و بازبین باید دو پروفایل جدا باشند.');
            }
        });

        static::updating(function (self $course): void {
            if ($course->isDirty(['author_id', 'reviewer_id']) && $course->author_id && $course->reviewer_id && $course->author_id === $course->reviewer_id) {
                throw new \InvalidArgumentException('نویسنده و بازبین باید دو پروفایل جدا باشند.');
            }
        });
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Contributor::class, 'author_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Contributor::class, 'reviewer_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function decks(): HasMany
    {
        return $this->hasMany(FlashcardDeck::class, 'course_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }
}
