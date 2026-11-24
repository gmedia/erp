<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Permission;
use Illuminate\Console\Command;

class GrantUserEditPermission extends Command
{
    /**
     * @var string
     */
    protected $signature = 'permissions:grant-user-edit {email : Email karyawan yang diberi izin user.edit}';

    /**
     * @var string
     */
    protected $description = 'Berikan permission user.edit kepada satu karyawan (dipakai saat pemulihan akses admin).';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $employee = Employee::query()->where('email', $email)->first();

        if ($employee === null) {
            $this->error("Karyawan dengan email {$email} tidak ditemukan.");

            return self::FAILURE;
        }

        $permission = Permission::query()->where('name', 'user.edit')->first();

        if ($permission === null) {
            $this->error('Permission user.edit belum ada. Jalankan migrate terlebih dahulu.');

            return self::FAILURE;
        }

        $employee->permissions()->syncWithoutDetaching([$permission->id]);

        $this->info("Permission user.edit diberikan kepada {$employee->email}.");

        return self::SUCCESS;
    }
}
