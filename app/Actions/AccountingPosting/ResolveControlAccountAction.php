<?php

namespace App\Actions\AccountingPosting;

use App\Models\Account;
use App\Models\AccountMapping;
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

        if ($account !== null) {
            return $account;
        }

        $counterpart = $this->resolveThroughMapping($accountCode, $coaVersion);

        if ($counterpart === null) {
            throw ValidationException::withMessages([
                'account' => __('accounting_posting.control_account_missing', ['code' => $accountCode]),
            ]);
        }

        return $counterpart;
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

    /**
     * Padanan kode $accountCode di COA version $coaVersion lewat account_mappings (#571).
     *
     * Kode kontrol di kode posting berasal dari versi COA terbaru. Bila kode itu tidak ada
     * di versi yang berlaku untuk tanggal jurnal (mis. 11110 pada COA 2025 yang hanya punya
     * 11100), telusuri mapping terbalik: akun $accountCode di versi lain sebagai target,
     * lalu sumbernya di $coaVersion. Hanya padanan tunggal yang diterima; nol atau lebih
     * dari satu padanan dianggap tidak ada (control_account_missing), agar tidak menebak.
     */
    private function resolveThroughMapping(string $accountCode, CoaVersion $coaVersion): ?Account
    {
        $targetIds = Account::query()
            ->where('code', $accountCode)
            ->where('is_active', true)
            ->where('coa_version_id', '!=', $coaVersion->id)
            ->pluck('id');

        if ($targetIds->isEmpty()) {
            return null;
        }

        $sourceIds = AccountMapping::query()
            ->whereIn('target_account_id', $targetIds)
            ->pluck('source_account_id');

        $accounts = Account::query()
            ->where('coa_version_id', $coaVersion->id)
            ->whereIn('id', $sourceIds)
            ->where('is_active', true)
            ->get();

        return $accounts->count() === 1 ? $accounts->first() : null;
    }

    private function ambiguous(string $entryDate): ValidationException
    {
        return ValidationException::withMessages([
            'coa_version' => __('accounting_posting.coa_ambiguous', ['date' => $entryDate]),
        ]);
    }
}
