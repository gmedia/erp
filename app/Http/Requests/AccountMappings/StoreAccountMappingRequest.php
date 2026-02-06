<?php

namespace App\Http\Requests\AccountMappings;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;

class StoreAccountMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'target_account_id' => ['required', 'integer', 'exists:accounts,id', 'different:source_account_id'],
            'type' => ['required', 'string', 'in:merge,split,rename'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $sourceId = $this->integer('source_account_id');
            $targetId = $this->integer('target_account_id');

            if (! $sourceId || ! $targetId) {
                return;
            }

            $source = Account::query()->select(['id', 'coa_version_id'])->find($sourceId);
            $target = Account::query()->select(['id', 'coa_version_id'])->find($targetId);

            if (! $source || ! $target) {
                return;
            }

            if ((int) $source->coa_version_id === (int) $target->coa_version_id) {
                $validator->errors()->add('target_account_id', 'Target account must be from a different COA version.');
            }
        });
    }
}
