<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('gr_number')->nullable()->unique();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->date('receipt_date');
            $table->string('supplier_delivery_note')->nullable();
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('purchase_order_id');
            $table->index('warehouse_id');
            $table->index('receipt_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
