<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['quiz_id', 'prompt', 'explanation', 'source_citation', 'sort_order', 'status', 'published_at', 'author_id', 'reviewer_id', 'is_free_designated'])]
class QuizQuestion extends Model
{
    use SoftDeletes;
    protected function casts(): array { return ['published_at' => 'datetime', 'is_free_designated' => 'boolean']; }
    public function quiz(): BelongsTo { return $this->belongsTo(Quiz::class); }
    public function options(): HasMany { return $this->hasMany(QuizOption::class); }
}
