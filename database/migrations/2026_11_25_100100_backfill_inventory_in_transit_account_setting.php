<?php

use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill setting akun "Persediaan dalam Perjalanan" (issue #495).
 *
 * Bila akun berkode 11310 aktif ada di COA aktif, setting diisi otomatis. Bila tidak ada,
 * setting dibiarkan kosong: pengiriman transfer ditolak sampai akun diatur di
 * Admin Settings > Accounting. Idempoten: setting yang sudah terisi tidak ditimpa.
 */
return new class extends Migration
{
    private const KEY = 'inventory_in_transit_account_id';

    private const CODE = '11310';

    public function up(): void
    {
        $setting = Setting::query()->where('key', self::KEY)->first();

        if ($setting === null) {
            return;
        }

        if ($setting->value !== null && $setting->value !== '') {
            return;
        }

        $coaVersion = CoaVersion::query()->where('status', 'active')->first();

        if ($coaVersion === null) {
            return;
        }

        $account = Account::query()
            ->where('coa_version_id', $coaVersion->id)
            ->where('code', self::CODE)
            ->where('is_active', true)
            ->first();

        if ($account === null) {
            return;
        }

        $setting->update(['value' => (string) $account->id]);
    }

    public function down(): void
    {
        // Tidak ada perubahan yang dikembalikan: nilai setting bisa diatur user setelah upgrade.
    }
};
