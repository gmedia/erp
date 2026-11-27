<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menambah permission `audit_log` (+ `audit_log.export`) pada instalasi lama.
 *
 * Idempoten. Izin baru diberikan hanya kepada karyawan yang sebelumnya memegang
 * SELURUH permission yang ada (setara peran admin penuh). Bila tidak ada karyawan
 * seperti itu, tidak ada yang diberi izin; berikan lewat Pengaturan Permission.
 */
return new class extends Migration
{
    /**
     * @var list<array{name: string, display_name: string, parent: string|null}>
     */
    private const PERMISSIONS = [
        [
            'name' => 'audit_log',
            'display_name' => 'Audit Log',
            'parent' => null,
        ],
        [
            'name' => 'audit_log.export',
            'display_name' => 'Export Audit Log',
            'parent' => 'audit_log',
        ],
    ];

    public function up(): void
    {
        $existingNames = DB::table('permissions')->pluck('name')->all();
        $preExistingIds = DB::table('permissions')->pluck('id')->all();

        foreach (self::PERMISSIONS as $permission) {
            if (in_array($permission['name'], $existingNames, true)) {
                continue;
            }

            $parentId = $permission['parent'] === null
                ? null
                : DB::table('permissions')->where('name', $permission['parent'])->value('id');

            DB::table('permissions')->insert([
                'name' => $permission['name'],
                'display_name' => $permission['display_name'],
                'parent_id' => $parentId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

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

        $newIds = DB::table('permissions')
            ->whereIn('name', array_column(self::PERMISSIONS, 'name'))
            ->pluck('id')
            ->all();

        $fullHolderIds = DB::table('employee_permission')
            ->whereIn('permission_id', $preExistingIds)
            ->select('employee_id')
            ->groupBy('employee_id')
            ->havingRaw('COUNT(DISTINCT permission_id) = ?', [count($preExistingIds)])
            ->pluck('employee_id');

        foreach ($fullHolderIds as $employeeId) {
            foreach ($newIds as $permissionId) {
                DB::table('employee_permission')->updateOrInsert(
                    ['employee_id' => $employeeId, 'permission_id' => $permissionId],
                    ['created_at' => now(), 'updated_at' => now()],
                );
            }
        }
    }
};
