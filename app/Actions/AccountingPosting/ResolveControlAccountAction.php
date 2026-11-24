<?php

namespace App\Actions\AccountingPosting;

use App\Models\Account;
use App\Models\CoaVersion;
use Illuminate\Validation\ValidationException;

class ResolveControlAccountAction
{
    /**
     * Resolve akun kontrol dari COA version yang berlaku untuk tanggal jurnal (#357).
     *
     * COA dipilih lewat tahun fiskal yang memuat $entryDate. Bila tahun fiskal itu
     * tidak punya COA aktif, atau lebih dari satu, posting ditolak dengan 422
     * agar tidak menebak akun yang salah.
     */
    public function execute(string $accountCode, string $entryDate): Account
    {
        $coaVersions = CoaVersion::query()
            ->where('status', 'active')
            ->whereHas('fiscalYear', fn ($query) => $query
                ->where('start_date', '<=', $entryDate)
                ->where('end_date', '>=', $entryDate))
            ->get();

        if ($coaVersions->isEmpty()) {
            throw ValidationException::withMessages([
                'coa_version' => "No active COA version found for date {$entryDate}. Seed or activate a COA version before posting.",
            ]);
        }

        if ($coaVersions->count() > 1) {
            throw ValidationException::withMessages([
                'coa_version' => "Multiple active COA versions found for date {$entryDate}. Keep exactly one active COA per fiscal year.",
            ]);
        }

        $account = Account::query()
            ->where('coa_version_id', $coaVersions->first()->id)
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
