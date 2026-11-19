<?php

namespace App\Actions\VehicleLoans;

use App\Models\VehicleLoan;
use Illuminate\Support\Facades\Storage;

class CleanupVehicleLoansAction
{
    /**
     * @return array<string, int>
     */
    public function execute(bool $dryRun): array
    {
        $stats = [
            'photos_deleted' => 0,
            'drafts_expired' => 0,
        ];

        $retentionDays = max(1, (int) config('vehicle_loan.photo_retention_days', 365));
        $cutoff = now()->subDays($retentionDays);
        $disk = Storage::disk(config('vehicle_loan.photo_disk', 'local'));

        foreach (['pickups', 'returns'] as $folder) {
            $files = collect($disk->allFiles("vehicle-loans/{$folder}"))
                ->filter(fn (string $path): bool => $disk->lastModified($path) < $cutoff->getTimestamp());

            foreach ($files as $path) {
                if (! $dryRun) {
                    $disk->delete($path);
                }
                $stats['photos_deleted']++;
            }
        }

        $expiryDays = max(1, (int) config('vehicle_loan.draft_expiry_days', 7));
        $drafts = VehicleLoan::query()
            ->where('status', 'draft')
            ->where('created_at', '<', now()->subDays($expiryDays))
            ->get();

        foreach ($drafts as $draft) {
            if (! $dryRun) {
                $draft->update(['status' => 'cancelled']);
            }
            $stats['drafts_expired']++;
        }

        return $stats;
    }
}
