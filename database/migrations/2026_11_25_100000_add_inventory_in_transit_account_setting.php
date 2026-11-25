<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Pengaturan akun "Persediaan dalam Perjalanan" (issue #495).
 * Nilai kosong = belum diatur; jurnal barang dalam perjalanan ditolak sampai diisi.
 */
return new class extends Migration
{
    private const KEY = 'inventory_in_transit_account_id';

    public function up(): void
    {
        Setting::query()->updateOrCreate(
            ['key' => self::KEY],
            [
                'group' => 'accounting',
                'key' => self::KEY,
                'value' => null,
                'type' => 'string',
            ]
        );
    }

    public function down(): void
    {
        Setting::query()->where('key', self::KEY)->delete();
    }
};
