<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['name', 'slug'])]
class Tag extends Model
{
    use HasFactory;
    public function courses(): BelongsToMany { return $this->belongsToMany(Course::class); }
}
