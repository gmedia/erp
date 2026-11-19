<?php

namespace App\Actions\VehicleLoans;

use App\Models\VehicleLoan;
use App\Models\VehicleLoanActivityLog;
use App\Notifiables\VehicleLoanDueEvent;
use App\Services\NotificationService;
use Illuminate\Support\Collection;

class NotifyOverdueVehicleLoansAction
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * @return array<string, int>
     */
    public function execute(bool $dryRun): array
    {
        $loans = VehicleLoan::query()
            ->whereIn('status', ['approved', 'pickup_done'])
            ->where('end_at', '<', now())
            ->where('is_overdue', false)
            ->with('requester')
            ->get();

        $notified = 0;

        foreach ($loans as $loan) {
            if (! $dryRun) {
                $loan->update(['is_overdue' => true]);

                VehicleLoanActivityLog::create([
                    'vehicle_loan_id' => $loan->id,
                    'event' => 'return_overdue',
                    'metadata' => ['end_at' => $loan->end_at?->toIso8601String()],
                ]);

                $this->notifications->send(new VehicleLoanDueEvent(
                    $loan,
                    new Collection([$loan->requester]),
                    [
                        'title' => 'Pengembalian kendaraan terlambat',
                        'body' => 'Permintaan ' . $loan->code . ' melewati jadwal pengembalian.',
                    ],
                ));
            }

            $notified++;
        }

        return ['notified' => $notified];
    }
}
