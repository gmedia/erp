<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('unit_id')->constrained('units');
            $table->decimal('quantity_received', 15, 2);
            $table->decimal('quantity_accepted', 15, 2);
            $table->decimal('quantity_rejected', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['goods_receipt_id', 'purchase_order_item_id'], 'gr_items_receipt_po_item_unique');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};
