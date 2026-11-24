<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menambahkan permission aksi (edit) yang belum tersedia:
 * - posting_journal.edit          : posting jurnal
 * - asset_depreciation_run.edit   : hitung dan posting run penyusutan aset
 *
 * Hanya menambah baris. Tidak memberi izin ke siapa pun.
 */
return new class extends Migration
{
    private const NEW_PERMISSIONS = [
        [
            'name' => 'posting_journal.edit',
            'display_name' => 'Edit Posting Journal',
            'parent' => 'posting_journal',
        ],
        [
            'name' => 'asset_depreciation_run.edit',
            'display_name' => 'Edit Asset Depreciation Run',
            'parent' => 'asset_depreciation_run',
        ],
    ];

    public function up(): void
    {
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
