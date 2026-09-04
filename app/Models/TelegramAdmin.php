<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramAdmin extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_user_id',
        'username',
        'first_name',
        'last_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'telegram_user_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
