<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();

            $table->enum('movement_type', [
                'goods_receipt',
                'supplier_return',
                'transfer_out',
                'transfer_in',
                'adjustment_in',
                'adjustment_out',
                'production_consume',
                'production_output',
                'sales',
                'sales_return',
            ]);

            $table->decimal('quantity_in', 15, 2)->default(0);
            $table->decimal('quantity_out', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2)->default(0);

            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('average_cost_after', 15, 2)->nullable();

            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number')->nullable();

            $table->text('notes')->nullable();
            $table->timestamp('moved_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['product_id', 'warehouse_id', 'moved_at']);
            $table->index('movement_type');
            $table->index(['reference_type', 'reference_id']);
            $table->index('moved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};

