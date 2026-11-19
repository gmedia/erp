<?php

namespace App\Console\Commands;

use App\Models\SocialLoginTicket;
use Illuminate\Console\Command;

class CleanupSocialLoginTickets extends Command
{
    protected $signature = 'social:cleanup-tickets';

    protected $description = 'Delete expired social login tickets';

    public function handle(): int
    {
        $deleted = SocialLoginTicket::query()
            ->where('expires_at', '<', now()->subDay())
            ->delete();

        $this->info("Deleted {$deleted} expired social login ticket(s).");

        return self::SUCCESS;
    }
}
