<?php

namespace App\Console\Commands;

use App\Models\TelegramAdmin;
use Illuminate\Console\Command;

class ManageTelegramAdmin extends Command
{
    protected $signature = 'broca:telegram-admin {user_id : Numeric Telegram user ID} {--username=} {--first-name=} {--last-name=} {--disable : Disable instead of enable}';

    protected $description = 'Add or update an allowed Telegram admin for the Broca admin bot';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');
        if ($userId < 1) {
            $this->error('Telegram user ID must be a positive integer.');

            return self::INVALID;
        }

        $admin = TelegramAdmin::query()->updateOrCreate(
            ['telegram_user_id' => $userId],
            [
                'username' => $this->option('username') ?: null,
                'first_name' => $this->option('first-name') ?: null,
                'last_name' => $this->option('last-name') ?: null,
                'is_active' => ! (bool) $this->option('disable'),
            ],
        );

        $this->info(($admin->is_active ? 'Enabled' : 'Disabled').' Telegram admin #'.$admin->telegram_user_id);

        return self::SUCCESS;
    }
}
