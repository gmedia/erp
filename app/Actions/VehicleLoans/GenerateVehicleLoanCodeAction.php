<?php

namespace App\Actions\VehicleLoans;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateVehicleLoanCodeAction
{
    public function execute(Carbon $date): string
    {
        $prefix = 'VL-' . $date->format('Ymd') . '-';

        $last = DB::table('vehicle_loans')
            ->where('code', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $sequence = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
