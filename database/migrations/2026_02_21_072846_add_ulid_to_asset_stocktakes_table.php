<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('asset_stocktakes', 'ulid')) {
            Schema::table('asset_stocktakes', function (Blueprint $table) {
                $table->ulid('ulid')->nullable()->after('id')->index();
            });
        }

        // Initialize existing records with ULIDs if any are still null
        \App\Models\AssetStocktake::whereNull('ulid')->each(function ($item) {
            $item->update(['ulid' => (string) \Illuminate\Support\Str::ulid()]);
        });

        Schema::table('asset_stocktakes', function (Blueprint $table) {
            $table->ulid('ulid')->nullable(false)->change()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_stocktakes', function (Blueprint $table) {
            $table->dropColumn('ulid');
        });
    }
};
