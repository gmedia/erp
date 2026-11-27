<?php

namespace App\Console\Commands;

use App\Health\SystemHealthRunner;
use Illuminate\Console\Command;

class RunSystemHealthChecks extends Command
{
    protected $signature = 'system-health:run';

    protected $description = 'Jalankan semua pemeriksaan kesehatan sistem, kirim notifikasi perubahan status, dan simpan riwayat.';

    public function handle(SystemHealthRunner $runner): int
    {
        $results = $runner->run();

        $failing = $results->filter(
            fn ($result): bool => in_array((string) $result->status, ['warning', 'failed', 'crashed'], true)
        );

        $this->info(sprintf('Pemeriksaan selesai: %d komponen, %d perlu perhatian.', $results->count(), $failing->count()));

        return $failing->contains(fn ($result): bool => in_array((string) $result->status, ['failed', 'crashed'], true))
            ? self::FAILURE
            : self::SUCCESS;
    }
}
