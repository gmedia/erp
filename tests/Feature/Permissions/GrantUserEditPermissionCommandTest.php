<?php

use App\Models\Employee;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class)->group('route-permission-scan');

it('memberi user.edit kepada satu karyawan berdasarkan email', function () {
    $target = Employee::factory()->create(['email' => 'pengelola@example.com']);
    $other = Employee::factory()->create(['email' => 'lainnya@example.com']);

    expect(Artisan::call('permissions:grant-user-edit', ['email' => 'pengelola@example.com']))->toBe(0);

    $userEditId = Permission::where('name', 'user.edit')->value('id');

    expect($target->permissions()->where('permissions.id', $userEditId)->exists())->toBeTrue();
    expect($other->permissions()->where('permissions.id', $userEditId)->exists())->toBeFalse();
});

it('idempoten: menjalankan dua kali tidak menduplikasi izin', function () {
    $target = Employee::factory()->create(['email' => 'pengelola@example.com']);

    Artisan::call('permissions:grant-user-edit', ['email' => 'pengelola@example.com']);
    Artisan::call('permissions:grant-user-edit', ['email' => 'pengelola@example.com']);

    $userEditId = Permission::where('name', 'user.edit')->value('id');

    expect($target->permissions()->where('permissions.id', $userEditId)->count())->toBe(1);
});

it('gagal dengan pesan jelas bila email karyawan tidak ditemukan', function () {
    expect(Artisan::call('permissions:grant-user-edit', ['email' => 'tidak-ada@example.com']))->toBe(1);
});
