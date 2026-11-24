<?php

use App\Models\Account;
use App\Models\CoaVersion;
use Illuminate\Database\Migrations\Migration;

/**
 * Menambah akun perantara GR/IR (21150) ke setiap COA version yang belum memilikinya.
 * Hanya menambah baris akun; jurnal lama tidak disentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        CoaVersion::query()->each(function (CoaVersion $coaVersion): void {
            $exists = Account::query()
                ->where('coa_version_id', $coaVersion->id)
                ->where('code', '21150')
                ->exists();

            if ($exists) {
                return;
            }

            $parent = Account::query()
                ->where('coa_version_id', $coaVersion->id)
                ->where('code', '21000')
                ->first();

            Account::create([
                'coa_version_id' => $coaVersion->id,
                'parent_id' => $parent?->id,
                'code' => '21150',
                'name' => 'Goods Received Not Invoiced (GR/IR)',
                'type' => 'liability',
                'sub_type' => 'current_liability',
                'normal_balance' => 'credit',
                'level' => $parent ? $parent->level + 1 : 3,
                'is_active' => true,
                'is_cash_flow' => false,
            ]);
        });
    }

    public function down(): void
    {
        // Hanya menghapus akun 21150 yang belum dipakai jurnal, agar jurnal posted tidak rusak.
        Account::query()
            ->where('code', '21150')
            ->whereDoesntHave('journalLines')
            ->delete();
    }
};
