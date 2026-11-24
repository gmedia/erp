<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Pengaturan akun laba dan rugi pelepasan aset (#442).
 * Nilai kosong = belum diatur; posting jurnal pelepasan ditolak sampai diisi.
 */
return new class extends Migration
{
    private const KEYS = [
        'asset_disposal_gain_account_id',
        'asset_disposal_loss_account_id',
    ];

    public function up(): void
    {
        foreach (self::KEYS as $key) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'group' => 'accounting',
                    'key' => $key,
                    'value' => null,
                    'type' => 'string',
                ]
            );
        }
    }

    public function down(): void
    {
        Setting::query()->whereIn('key', self::KEYS)->delete();
    }
};
