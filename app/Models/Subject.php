<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['name', 'slug', 'description', 'sort_order', 'is_visible'])]
class Subject extends Model
{
    use HasFactory, SoftDeletes;
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
