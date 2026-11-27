<?php

use App\Health\SystemHealthThresholds;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Menambah grup Setting "system_health" (ambang kesehatan) dan permission `system_health`
 * pada instalasi yang sudah berjalan. Idempoten: hanya menambah baris yang belum ada.
 * Permission ini tidak diberikan ke siapa pun di sini; peran admin diberi lewat
 * PermissionSeeder dan sinkronisasi admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (SystemHealthThresholds::definitions() as $key => $definition) {
            Setting::query()->firstOrCreate(
                ['key' => $key],
                [
                    'group' => SystemHealthThresholds::GROUP,
                    'value' => (string) $definition['default'],
                    'type' => 'integer',
                ],
            );
        }

        // Alamat email admin IT untuk notifikasi (kosong = tidak kirim email).
        Setting::query()->firstOrCreate(
            ['key' => SystemHealthThresholds::ALERT_EMAIL],
            [
                'group' => SystemHealthThresholds::GROUP,
                'value' => null,
                'type' => 'string',
            ],
        );

        $permission = Permission::query()->updateOrCreate(
            ['name' => 'system_health'],
            ['display_name' => 'System Health', 'parent_id' => null],
        );

        // Karyawan yang sudah memegang admin_setting.edit (admin pengaturan) ikut diberi akses
        // agar admin yang ada tidak kehilangan halaman baru. Penambahan bersifat idempoten.
        $adminSetting = Permission::query()->where('name', 'admin_setting.edit')->first();

        if ($adminSetting === null) {
            return;
        }

        Employee::query()
            ->whereHas('permissions', fn ($query) => $query->whereKey($adminSetting->id))
            ->each(fn (Employee $employee) => $employee->permissions()->syncWithoutDetaching([$permission->id]));
    }

    public function down(): void
    {
        Setting::query()
            ->where('group', SystemHealthThresholds::GROUP)
            ->delete();

        Permission::query()->where('name', 'system_health')->delete();
    }
};
