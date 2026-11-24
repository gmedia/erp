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

            // Pemetaan hanya untuk akun posting; akun kelompok (punya anak) ditolak.
            $message = 'Account must be a postable account, not a group account.';

            if (Account::query()->whereKey($target->id)->whereHas('children')->exists()) {
                $validator->errors()->add('target_account_id', $message);
            }

            if (Account::query()->whereKey($source->id)->whereHas('children')->exists()) {
                $validator->errors()->add('source_account_id', $message);
            }
        });
    }
}
