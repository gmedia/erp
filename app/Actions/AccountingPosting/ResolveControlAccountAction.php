<?php

namespace App\Actions\AccountingPosting;

use App\Models\Account;
use App\Models\CoaVersion;
use Illuminate\Validation\ValidationException;

class ResolveControlAccountAction
{
    public function execute(string $accountCode): Account
    {
        $coaVersion = CoaVersion::where('status', 'active')->first();

        if ($coaVersion === null) {
            throw ValidationException::withMessages([
                'coa_version' => 'No active COA version found. Seed or activate a COA version before posting.',
            ]);
        }

        $account = Account::where('coa_version_id', $coaVersion->id)
            ->where('code', $accountCode)
            ->where('is_active', true)
            ->first();

        if ($account === null) {
            throw ValidationException::withMessages([
                'account' => "Control account with code '{$accountCode}' was not found in active COA version.",
            ]);
        }

        return $account;
    }
}
