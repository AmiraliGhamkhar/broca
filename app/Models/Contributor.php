<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['name', 'slug', 'credentials', 'specialty', 'bio', 'photo_path', 'is_visible'])]
class Contributor extends Model
{
    use HasFactory, SoftDeletes;

    public function authoredCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'author_id');
    }

    public function reviewedCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'reviewer_id');
    }
}
