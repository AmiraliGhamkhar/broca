<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Copies the committed placeholder media (database/placeholder-media) into
 * the runtime locations the app streams from:
 *
 *   - public/videos/{manifest_reference}   → signed playback chain
 *   - storage/app/private/notes/*.pdf      → 'local' disk note downloads
 *
 * Both destinations are gitignored, so a fresh clone needs this command
 * (or `php artisan db:seed`, which runs it) before seeded content plays.
 * Swap the files in database/placeholder-media for the real assets and
 * re-run — nothing in the code changes.
 */
class ProvisionPlaceholderMedia extends Command
{
    protected $signature = 'broca:provision-media';

    protected $description = 'Copy placeholder video/note assets into the runtime storage locations';

    public function handle(): int
    {
        $sourceVideos = database_path('placeholder-media/videos');
        $sourceNotes = database_path('placeholder-media/notes');

        $destVideos = public_path('videos');
        $destNotes = storage_path('app/private/notes');

        if (! is_dir($sourceVideos) || ! is_dir($sourceNotes)) {
            $this->error('Placeholder media sources missing under database/placeholder-media.');

            return self::FAILURE;
        }

        File::ensureDirectoryExists($destVideos);
        File::ensureDirectoryExists($destNotes);

        $copied = 0;
        foreach (File::files($sourceVideos) as $file) {
            File::copy($file->getPathname(), $destVideos.'/'.$file->getFilename());
            $this->line("  video  → public/videos/{$file->getFilename()}");
            $copied++;
        }
        foreach (File::files($sourceNotes) as $file) {
            File::copy($file->getPathname(), $destNotes.'/'.$file->getFilename());
            $this->line("  note   → storage/app/private/notes/{$file->getFilename()}");
            $copied++;
        }

        $this->info("Provisioned {$copied} placeholder file(s).");

        return self::SUCCESS;
    }
}
