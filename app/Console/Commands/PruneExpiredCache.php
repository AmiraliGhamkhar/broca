<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Deletes expired rows from the database cache store (Round-6 audit D-2).
 *
 * The database cache driver never garbage-collects: expired entries are
 * filtered on read and only overwritten when the same key is re-set. Rate
 * limiter keys (throttle:*) create a new row per user per window, so on a
 * long-lived production database the cache table grows without bound and
 * every cache read wades through millions of dead rows. Laravel ships
 * `cache:prune-stale-tags` for Redis/Memcached only — the database driver
 * has no built-in pruner, hence this command (no new composer dependency).
 *
 * Companion to the framework's own `session:prune` (scheduled alongside it
 * in routes/console.php), which handles the sessions table (audit D-1).
 */
class PruneExpiredCache extends Command
{
    protected $signature = 'broca:prune-cache';

    protected $description = 'Delete expired rows from the database cache and cache_locks tables';

    public function handle(): int
    {
        $cutoff = now()->getTimestamp();

        $cacheDeleted = DB::table('cache')->where('expiration', '<', $cutoff)->delete();
        $locksDeleted = DB::table('cache_locks')->where('expiration', '<', $cutoff)->delete();

        $this->info("Pruned {$cacheDeleted} expired cache row(s) and {$locksDeleted} expired lock(s).");

        return self::SUCCESS;
    }
}
