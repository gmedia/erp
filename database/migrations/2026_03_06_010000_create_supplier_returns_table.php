<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->nullable()->unique();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders');
            $table->foreignId('goods_receipt_id')->nullable()->constrained('goods_receipts')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->date('return_date');
            $table->enum('reason', ['defective', 'wrong_item', 'excess_quantity', 'damaged', 'other']);
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('purchase_order_id');
            $table->index('goods_receipt_id');
            $table->index('supplier_id');
            $table->index('return_date');
            $table->index('status');
            $table->index('reason');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_returns');
    }
};
