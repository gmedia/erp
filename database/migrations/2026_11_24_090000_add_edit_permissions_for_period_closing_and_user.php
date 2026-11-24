<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menambahkan permission aksi (edit) yang belum tersedia:
 * - period_closing.edit : close, reopen, dan export period closing
 * - user.edit           : atur user & permission karyawan
 *
 * Hanya menambah baris. Tidak memberi izin otomatis, kecuali untuk mencegah
 * kondisi terkunci total: bila tidak ada satu pun karyawan yang memegang
 * user.edit, user.edit diberikan kepada karyawan yang memegang SEMUA
 * permission yang sudah ada sebelum migration ini.
 */
return new class extends Migration
{
    private const NEW_PERMISSIONS = [
        [
            'name' => 'period_closing.edit',
            'display_name' => 'Edit Period Closing',
            'parent' => 'period_closing',
        ],
        [
            'name' => 'user.edit',
            'display_name' => 'Edit User',
            'parent' => 'user',
        ],
    ];

    public function up(): void
    {
        $existingPermissionIds = DB::table('permissions')
            ->whereNotIn('name', array_column(self::NEW_PERMISSIONS, 'name'))
            ->pluck('id')
            ->all();

        foreach (self::NEW_PERMISSIONS as $permission) {
            $parentId = DB::table('permissions')->where('name', $permission['parent'])->value('id');

            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                [
                    'display_name' => $permission['display_name'],
                    'parent_id' => $parentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        $userEditId = DB::table('permissions')->where('name', 'user.edit')->value('id');

        $hasUserEditHolder = DB::table('employee_permission')
            ->where('permission_id', $userEditId)
            ->exists();

        if ($hasUserEditHolder || $existingPermissionIds === []) {
            return;
        }

        $employeeIds = DB::table('employee_permission')
            ->whereIn('permission_id', $existingPermissionIds)
            ->select('employee_id')
            ->groupBy('employee_id')
            ->havingRaw('COUNT(DISTINCT permission_id) = ?', [count($existingPermissionIds)])
            ->pluck('employee_id');

        foreach ($employeeIds as $employeeId) {
            DB::table('employee_permission')->updateOrInsert(
                ['employee_id' => $employeeId, 'permission_id' => $userEditId],
                ['created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_column(self::NEW_PERMISSIONS, 'name'))
            ->pluck('id');

        DB::table('employee_permission')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
