<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hasil penjualan pada pelepasan aset (#442, langkah 2).
 * Nilai null/0 = pelepasan tanpa hasil penjualan (rugi = nilai buku).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_movements', function (Blueprint $table) {
            $table->decimal('sale_amount', 18, 2)->nullable()->after('status_before_disposal');
            $table->foreignId('sale_account_id')->nullable()->after('sale_amount')
                ->constrained('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('asset_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_account_id');
            $table->dropColumn('sale_amount');
        });
    }
};
