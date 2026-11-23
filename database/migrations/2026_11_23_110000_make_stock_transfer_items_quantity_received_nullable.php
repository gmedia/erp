<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->decimal('quantity_received', 15, 2)->nullable()->default(null)->change();
        });

        DB::table('stock_transfer_items')
            ->join('stock_transfers', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
            ->where('stock_transfers.status', '!=', 'received')
            ->where('stock_transfer_items.quantity_received', 0)
            ->update(['stock_transfer_items.quantity_received' => null]);
    }

    public function down(): void
    {
        DB::table('stock_transfer_items')
            ->whereNull('quantity_received')
            ->update(['quantity_received' => 0]);

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->decimal('quantity_received', 15, 2)->default(0)->nullable(false)->change();
        });
    }
};
