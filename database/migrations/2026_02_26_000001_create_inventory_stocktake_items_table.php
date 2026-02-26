<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stocktake_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_stocktake_id')->constrained('inventory_stocktakes')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->decimal('system_quantity', 15, 2)->default(0);
            $table->decimal('counted_quantity', 15, 2)->nullable();
            $table->decimal('variance', 15, 2)->nullable();
            $table->enum('result', ['match', 'surplus', 'deficit', 'uncounted'])->default('uncounted');
            $table->text('notes')->nullable();
            $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();

            $table->unique(['inventory_stocktake_id', 'product_id'], 'inv_stocktake_items_unique');
            $table->index(['inventory_stocktake_id', 'product_id'], 'inv_stocktake_items_stock_prod_idx');
            $table->index('result');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocktake_items');
    }
};
