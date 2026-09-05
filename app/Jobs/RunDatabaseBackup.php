<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

/**
 * Runs the database dump off the webhook request (Round-6 audit I-4).
 *
 * The backup is heavy: mysqldump + gzip + a multipart upload to Telegram.
 * Executing it synchronously inside the Telegram webhook can outlive
 * Telegram's ~60s callback patience, after which Telegram retries and the
 * same dump runs twice. Dispatching to the database queue moves the work
 * onto the cron-driven worker (routes/console.php, every minute,
 * --stop-when-empty) — infrastructure that already exists on cPanel, no
 * daemon required.
 */
class RunDatabaseBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    /** The dump command itself allows 10 minutes; never exceed it here. */
    public int $timeout = 600;

    public function __construct(public readonly int $chatId)
    {
    }

    public function handle(\App\Services\Telegram\TelegramApiClient $api): void
    {
        $exit = Artisan::call('broca:backup-database');
        $output = trim(Artisan::output());

        if ($exit !== 0) {
            $api->sendMessage($this->chatId, '❌ بکاپ انجام نشد.'.($output !== '' ? "\n\n".mb_substr($output, 0, 3200) : ''));

            return;
        }

        $files = glob(storage_path('app/backups/broca-*.sql.gz')) ?: [];
        rsort($files);
        $latest = $files[0] ?? null;

        if (! $latest) {
            $api->sendMessage($this->chatId, '✅ بکاپ اجرا شد، ولی فایل پیدا نشد.');

            return;
        }

        $api->sendDocument($this->chatId, $latest, 'بکاپ دیتابیس بروکا');
    }

    public function failed(\Throwable $exception): void
    {
        report($exception);

        try {
            app(\App\Services\Telegram\TelegramApiClient::class)
                ->sendMessage($this->chatId, '❌ بکاپ انجام نشد؛ خطا ثبت شد. دوباره تلاش کنید.');
        } catch (\Throwable) {
            // The chat may be unreachable; the report() above is the record.
        }
    }
}
