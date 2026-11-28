<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menambah permission `mobile_app_release` pada instalasi lama.
 *
 * Idempoten. Izin baru diberikan hanya kepada karyawan yang sebelumnya memegang
 * SELURUH permission yang ada (setara peran admin penuh). Bila tidak ada karyawan
 * seperti itu, tidak ada yang diberi izin; berikan lewat Pengaturan Permission.
 */
return new class extends Migration
{
    private const PERMISSION_NAME = 'mobile_app_release';

    public function up(): void
    {
        $existingNames = DB::table('permissions')->pluck('name')->all();

        if (in_array(self::PERMISSION_NAME, $existingNames, true)) {
            return;
        }

        $preExistingIds = DB::table('permissions')->pluck('id')->all();

        DB::table('permissions')->insert([
            'name' => self::PERMISSION_NAME,
            'display_name' => 'Mobile App Release',
            'parent_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->grantToFullHolders($preExistingIds);
    }

    public function down(): void
    {
        // Sengaja tidak menghapus apa pun agar rollback tidak menghilangkan data izin.
    }

    /**
     * @param  list<int>  $preExistingIds  permission yang sudah ada sebelum migration ini
     */
    private function grantToFullHolders(array $preExistingIds): void
    {
        if ($preExistingIds === []) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('name', self::PERMISSION_NAME)
            ->value('id');

        $fullHolderIds = DB::table('employee_permission')
            ->whereIn('permission_id', $preExistingIds)
            ->select('employee_id')
            ->groupBy('employee_id')
            ->havingRaw('COUNT(DISTINCT permission_id) = ?', [count($preExistingIds)])
            ->pluck('employee_id');

        foreach ($fullHolderIds as $employeeId) {
            DB::table('employee_permission')->updateOrInsert(
                ['employee_id' => $employeeId, 'permission_id' => $permissionId],
                ['created_at' => now(), 'updated_at' => now()],
            );
        }
    }
};
