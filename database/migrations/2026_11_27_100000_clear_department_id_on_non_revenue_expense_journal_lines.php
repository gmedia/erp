<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bersihkan dimensi departemen dari baris jurnal yang akunnya BUKAN pendapatan/beban (#563).
 *
 * Hanya kolom label `department_id` yang dikosongkan; `debit`/`credit` tidak diubah,
 * jadi saldo dan total laporan tidak berubah. Aturan ini sama dengan
 * App\Support\Accounting\DepartmentPostingRule::DEPARTMENT_ACCOUNT_TYPES.
 *
 * Idempoten: baris yang sudah NULL tidak tersentuh lagi, jadi aman dijalankan ulang.
 *
 * down() sengaja no-op: nilai departemen lama pada baris aset/kewajiban/ekuitas tidak
 * tersimpan di mana pun setelah dikosongkan, dan tidak ada sumber kebenaran untuk
 * memulihkannya. Memulihkan secara tebakan akan menghasilkan data yang salah.
 */
return new class extends Migration
{
    // Literal sengaja (bukan konstanta app) agar migrasi tidak berubah bila kode aplikasi berubah.
    private const DEPARTMENT_ACCOUNT_TYPES = ['revenue', 'expense'];

    public function up(): void
    {
        if (! Schema::hasColumn('journal_entry_lines', 'department_id')
            || ! Schema::hasTable('accounts')) {
            return;
        }

        // Subquery id akun non pendapatan/beban. Portabel MariaDB + SQLite: tanpa UPDATE ... JOIN.
        $nonDepartmentAccountIds = DB::table('accounts')
            ->whereNotIn('type', self::DEPARTMENT_ACCOUNT_TYPES)
            ->select('id');

        DB::table('journal_entry_lines')
            ->whereNotNull('department_id')
            ->whereIn('account_id', $nonDepartmentAccountIds)
            ->update(['department_id' => null]);
    }

    public function down(): void
    {
        // No-op by design (lihat docblock): nilai department_id lama tidak dapat dipulihkan.
    }
};
