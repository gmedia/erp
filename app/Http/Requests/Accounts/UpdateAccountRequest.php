<?php

namespace App\Http\Requests\Accounts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $account = $this->route('account');

        return [
            'coa_version_id' => ['required', 'exists:coa_versions,id'],
            'parent_id' => ['nullable', 'exists:accounts,id'],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('accounts')
                    ->where(fn ($query) => $query->where('coa_version_id', $this->coa_version_id))
                    ->ignore($account->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,equity,revenue,expense'],
            'sub_type' => ['nullable', 'string', 'max:255'],
            'normal_balance' => ['required', 'in:debit,credit'],
            'level' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'is_cash_flow' => ['boolean'],
            'description' => ['nullable', 'string'],
        ];
    }
}
