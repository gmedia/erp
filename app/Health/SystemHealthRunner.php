<?php

namespace App\Health;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Spatie\Health\Enums\Status;
use Spatie\Health\Facades\Health;
use Spatie\Health\ResultStores\EloquentHealthResultStore;
use Throwable;

/**
 * Menjalankan seluruh cek kesehatan, mengirim notifikasi perubahan status, lalu menyimpan
 * hasil ke riwayat. Dipakai oleh scheduler (tiap 5 menit) dan tombol "Periksa sekarang".
 */
class SystemHealthRunner
{
    public function __construct(private readonly SystemHealthNotifier $notifier)
    {
        // Intentionally empty. Dependencies are assigned via promoted properties.
    }

    /**
     * @return Collection<int, Result>
     */
    public function run(): Collection
    {
        $runId = (string) Str::uuid();

        $results = Health::registeredChecks()
            ->map(fn (Check $check): Result => $this->runCheck($check))
            ->values();

        $this->notifier->notifyTransitions($results, $runId);

        $this->store($results);

        return $results;
    }

    private function runCheck(Check $check): Result
    {
        try {
            $result = $check->shouldRun() ? $check->run() : new Result(Status::skipped());
        } catch (Throwable $exception) {
            report($exception);

            $result = $check->markAsCrashed();
        }

        return $result
            ->check($check)
            ->endedAt(now());
    }

    /**
     * @param  Collection<int, Result>  $results
     */
    private function store(Collection $results): void
    {
        (new EloquentHealthResultStore)->save($results);
    }
}
