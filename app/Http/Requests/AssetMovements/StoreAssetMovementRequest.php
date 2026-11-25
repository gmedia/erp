<?php

namespace App\Http\Requests\AssetMovements;

use Illuminate\Validation\Rule;

class StoreAssetMovementRequest extends AbstractAssetMovementRequest
{
    public function rules(): array
    {
        return array_merge(
            [
                // Field sistem diatur server, bukan payload (#442).
                'status' => ['prohibited'],
                'status_before_disposal' => ['prohibited'],
                'accumulated_depreciation' => ['prohibited'],
                'book_value' => ['prohibited'],
                'created_by' => ['prohibited'],
                'asset_id' => ['required', 'exists:assets,id'],
                'movement_type' => [
                    'required',
                    'string',
                    Rule::in(['acquired', 'transfer', 'assign', 'return', 'dispose', 'adjustment']),
                ],
                'to_branch_id' => [
                    'nullable',
                    'exists:branches,id',
                    Rule::requiredIf(fn () => in_array($this->movement_type, ['transfer'])),
                ],
                'to_location_id' => [
                    'nullable',
                    'exists:asset_locations,id',
                    Rule::requiredIf(fn () => in_array($this->movement_type, ['transfer'])),
                ],
                'to_department_id' => [
                    'nullable',
                    'exists:departments,id',
                    Rule::requiredIf(fn () => in_array($this->movement_type, ['assign'])),
                ],
                'to_employee_id' => [
                    'nullable',
                    'exists:employees,id',
                    Rule::requiredIf(fn () => in_array($this->movement_type, ['assign'])),
                ],
                // Hasil penjualan pelepasan (#442): opsional, tapi bila diisi wajib > 0 dan ada akun penerimaan.
                'sale_amount' => [
                    'nullable',
                    'numeric',
                    'gt:0',
                    Rule::requiredIf(fn () => $this->filled('sale_account_id')),
                ],
                'sale_account_id' => [
                    'nullable',
                    'integer',
                    'exists:accounts,id',
                    Rule::requiredIf(fn () => $this->filled('sale_amount')),
                ],
            ],
            $this->baseRules(),
        );
    }
}
