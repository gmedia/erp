<?php

namespace App\Http\Requests\VehicleLoans;

use App\Http\Requests\AuthorizedFormRequest;

class HandoverVehicleLoanKeyRequest extends AuthorizedFormRequest
{
    /**
     * Intentionally empty: handover is permission-gated at the route level.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
