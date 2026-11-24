<?php

namespace App\Http\Requests\AccountMappings;

use App\Http\Requests\AuthorizedFormRequest;
use App\Models\Account;

abstract class AbstractAccountMappingRequest extends AuthorizedFormRequest
{
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

            // Target harus akun daun (posting). Sumber boleh akun kelompok: instalasi lama
            // bisa punya saldo di akun kelompok yang perlu dipetakan ke akun daun.
            if (Account::query()->whereKey($target->id)->whereHas('children')->exists()) {
                $validator->errors()->add(
                    'target_account_id',
                    __('account_mapping.target_must_be_leaf'),
                );
            }
        });
    }
}
