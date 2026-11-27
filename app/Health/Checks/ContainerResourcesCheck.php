<?php

namespace App\Health\Checks;

use App\Health\SystemHealthMessage;
use App\Health\SystemHealthThresholds;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

/**
 * Beban CPU dan memori. Angka ini milik CONTAINER (cgroup) tempat aplikasi berjalan,
 * bukan host. Jika cgroup tidak terbaca (mis. di luar Linux), status dilaporkan "tidak tersedia".
 */
class ContainerResourcesCheck extends Check
{
    private const MEMORY_MAX = '/sys/fs/cgroup/memory.max';

    private const MEMORY_CURRENT = '/sys/fs/cgroup/memory.current';

    private const CPU_MAX = '/sys/fs/cgroup/cpu.max';

    public function run(): Result
    {
        $memory = $this->memoryUsagePercent();

        if ($memory === null) {
            return Result::make()
                ->meta([
                    'scope' => 'container',
                    'cpu_load_1m' => $this->loadAverage(),
                    'cpu_load_scope' => 'kernel',
                ] + SystemHealthMessage::meta('system_health.container.unavailable'))
                ->shortSummary('Tidak tersedia')
                ->warning('Pembacaan memori container tidak tersedia di lingkungan ini.');
        }

        [$usedMb, $limitMb, $percent] = $memory;

        $baseMeta = [
            'scope' => 'container',
            'memory_used_mb' => $usedMb,
            'memory_limit_mb' => $limitMb,
            'memory_used_percent' => $percent,
            'cpu_cores' => $this->cpuCores(),
            'cpu_load_1m' => $this->loadAverage(),
            // loadavg berasal dari kernel (bersama host), bukan dari cgroup container.
            'cpu_load_scope' => 'kernel',
        ];

        $result = Result::make()
            ->shortSummary($percent . '% memori');

        $warning = SystemHealthThresholds::get(SystemHealthThresholds::MEMORY_WARNING_PERCENT);
        $failed = SystemHealthThresholds::get(SystemHealthThresholds::MEMORY_FAILED_PERCENT);

        if ($percent >= $failed) {
            return $result
                ->meta($baseMeta + SystemHealthMessage::meta('system_health.container.memory_failed', [
                    'percent' => $percent,
                    'failed_at' => $failed,
                ]))
                ->failed("Memori container terpakai {$percent}% (batas gagal {$failed}%).");
        }

        if ($percent >= $warning) {
            return $result
                ->meta($baseMeta + SystemHealthMessage::meta('system_health.container.memory_warning', [
                    'percent' => $percent,
                    'warning_at' => $warning,
                ]))
                ->warning("Memori container terpakai {$percent}% (batas peringatan {$warning}%).");
        }

        return $result
            ->meta($baseMeta + SystemHealthMessage::meta('system_health.container.memory_ok', [
                'percent' => $percent,
            ]))
            ->ok("Memori container terpakai {$percent}%.");
    }

    // protected: seam uji (test meng-override pembacaan cgroup/loadavg tanpa menyentuh /sys asli).
    protected function readFile(string $path): ?string
    {
        if (! is_readable($path)) {
            return null;
        }

        $value = trim((string) file_get_contents($path));

        return $value === '' ? null : $value;
    }

    /**
     * @return array{0: float, 1: float, 2: int}|null
     */
    private function memoryUsagePercent(): ?array
    {
        $current = $this->readInt(self::MEMORY_CURRENT);
        $maxRaw = $this->readFile(self::MEMORY_MAX);

        if ($current === null || $maxRaw === null || $maxRaw === 'max') {
            return null;
        }

        $limit = (int) $maxRaw;

        if ($limit <= 0) {
            return null;
        }

        $percent = (int) round($current / $limit * 100);

        return [round($current / 1048576, 1), round($limit / 1048576, 1), $percent];
    }

    private function cpuCores(): ?float
    {
        $raw = $this->readFile(self::CPU_MAX);

        if ($raw === null) {
            return null;
        }

        [$quota, $period] = array_pad(explode(' ', $raw), 2, '');

        if ($quota === 'max' || (int) $period <= 0) {
            return null;
        }

        return round((int) $quota / (int) $period, 2);
    }

    private function loadAverage(): ?float
    {
        $raw = $this->readFile('/proc/loadavg');

        if ($raw === null) {
            return null;
        }

        $parts = explode(' ', $raw);

        return is_numeric($parts[0]) ? (float) $parts[0] : null;
    }

    private function readInt(string $path): ?int
    {
        $value = $this->readFile($path);

        return $value !== null && is_numeric($value) ? (int) $value : null;
    }
}
