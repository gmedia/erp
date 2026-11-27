<?php

namespace App\Health\Checks;

use App\Health\SystemHealthMessage;
use App\Health\SystemHealthThresholds;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Symfony\Component\Process\Process;

/**
 * Sisa disk untuk storage aplikasi. Ambang diambil dari Setting (Pengaturan Admin).
 */
class DiskUsageHealthCheck extends Check
{
    public function run(): Result
    {
        $percent = $this->usedPercent();

        if ($percent === null) {
            return Result::make()
                ->meta(SystemHealthMessage::meta('system_health.disk.unavailable'))
                ->shortSummary('Tidak tersedia')
                ->warning('Pembacaan disk tidak tersedia.');
        }

        $warning = SystemHealthThresholds::get(SystemHealthThresholds::DISK_WARNING_PERCENT);
        $failed = SystemHealthThresholds::get(SystemHealthThresholds::DISK_FAILED_PERCENT);

        $result = Result::make()
            ->shortSummary($percent . '%');

        if ($percent >= $failed) {
            return $result
                ->meta(['disk_used_percent' => $percent] + SystemHealthMessage::meta('system_health.disk.failed', [
                    'percent' => $percent,
                    'failed_at' => $failed,
                ]))
                ->failed("Disk hampir penuh ({$percent}% terpakai, batas gagal {$failed}%).");
        }

        if ($percent >= $warning) {
            return $result
                ->meta(['disk_used_percent' => $percent] + SystemHealthMessage::meta('system_health.disk.warning', [
                    'percent' => $percent,
                    'warning_at' => $warning,
                ]))
                ->warning("Disk terpakai {$percent}% (batas peringatan {$warning}%).");
        }

        return $result
            ->meta(['disk_used_percent' => $percent] + SystemHealthMessage::meta('system_health.disk.ok', [
                'percent' => $percent,
            ]))
            ->ok("Disk terpakai {$percent}%.");
    }

    // protected: seam uji (test meng-override pembacaan disk tanpa menjalankan df sungguhan).
    protected function usedPercent(): ?int
    {
        $path = storage_path();

        $process = Process::fromShellCommandline('df -P ' . escapeshellarg($path));
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $lines = array_values(array_filter(explode("\n", trim($process->getOutput()))));
        $columns = preg_split('/\s+/', (string) end($lines));

        $capacity = $columns[4] ?? null;

        return is_string($capacity) && preg_match('/^(\d+)%$/', $capacity, $matches) === 1
            ? (int) $matches[1]
            : null;
    }
}
