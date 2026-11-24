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
     * Aturan:
     * 1. Tepat satu COA aktif tertaut ke tahun fiskal yang memuat $entryDate: pakai itu.
     * 2. Tidak ada COA aktif tertaut ke tahun itu, dan tepat satu COA aktif di seluruh
     *    sistem: pakai itu (satu COA boleh melayani beberapa tahun fiskal).
     * 3. Selain itu: 422, agar tidak menebak akun yang salah.
     */
    public function execute(string $accountCode, string $entryDate): Account
    {
        $coaVersion = $this->resolveVersion($entryDate);

        $account = Account::query()
            ->where('coa_version_id', $coaVersion->id)
            ->where('code', $accountCode)
            ->where('is_active', true)
            ->first();

        if ($account === null) {
            throw ValidationException::withMessages([
                'account' => __('accounting_posting.control_account_missing', ['code' => $accountCode]),
            ]);
        }

        return $account;
    }

    /**
     * COA version aktif yang berlaku untuk tanggal jurnal. Dipakai juga oleh
     * validasi baris jurnal agar seluruh akun berasal dari COA yang sama.
     */
    public function resolveVersion(string $entryDate): CoaVersion
    {
        $linked = CoaVersion::query()
            ->where('status', 'active')
            ->whereHas('fiscalYear', fn ($query) => $query
                ->where('start_date', '<=', $entryDate)
                ->where('end_date', '>=', $entryDate))
            ->get();

        if ($linked->count() === 1) {
            return $linked->first();
        }

        if ($linked->count() > 1) {
            throw $this->ambiguous($entryDate);
        }

        $all = CoaVersion::query()->where('status', 'active')->limit(2)->get();

        if ($all->count() === 1) {
            return $all->first();
        }

        if ($all->isEmpty()) {
            throw ValidationException::withMessages([
                'coa_version' => __('accounting_posting.coa_none_active', ['date' => $entryDate]),
            ]);
        }

        throw $this->ambiguous($entryDate);
    }

    private function ambiguous(string $entryDate): ValidationException
    {
        return ValidationException::withMessages([
            'coa_version' => __('accounting_posting.coa_ambiguous', ['date' => $entryDate]),
        ]);
    }
}
