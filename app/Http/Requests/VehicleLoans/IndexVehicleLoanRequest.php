<?php

namespace App\Http\Requests\VehicleLoans;

use App\Http\Requests\AuthorizedFormRequest;

class IndexVehicleLoanRequest extends AuthorizedFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'mine' => ['boolean'],
            'per_page' => ['integer', 'min:1', 'max:100'],
        ];
    }
}
