<?php

namespace App\Console\Commands;

use App\Models\DeviceToken;
use Illuminate\Console\Command;

class PruneStaleDeviceTokens extends Command
{
    protected $signature = 'device-tokens:prune {--days=90 : Age threshold in days}';

    protected $description = 'Delete device tokens that have not been seen recently';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $deleted = DeviceToken::query()
            ->where(function ($query) use ($cutoff) {
                $query->where('last_seen_at', '<', $cutoff)
                    ->orWhere(function ($query) use ($cutoff) {
                        $query->whereNull('last_seen_at')
                            ->where('created_at', '<', $cutoff);
                    });
            })
            ->delete();

        $this->info("Pruned {$deleted} stale device tokens.");

        return self::SUCCESS;
    }
}
