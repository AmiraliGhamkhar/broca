<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramChatSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_chat_id',
        'telegram_user_id',
        'workflow',
        'context',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'telegram_chat_id' => 'integer',
            'telegram_user_id' => 'integer',
            'context' => 'array',
            'expires_at' => 'datetime',
        ];
    }
}
