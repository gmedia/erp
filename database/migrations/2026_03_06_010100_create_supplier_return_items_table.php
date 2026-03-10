<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_return_id')->constrained('supplier_returns')->cascadeOnDelete();
            $table->foreignId('goods_receipt_item_id')->constrained('goods_receipt_items');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('quantity_returned', 15, 2);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['supplier_return_id', 'goods_receipt_item_id'], 'sr_items_sr_gr_item_unique');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_return_items');
    }
};
