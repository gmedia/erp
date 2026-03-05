<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number')->nullable()->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->date('adjustment_date');
            $table->enum('adjustment_type', ['damage', 'expired', 'shrinkage', 'correction', 'stocktake_result', 'initial_stock', 'other']);
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'cancelled'])->default('draft');
            $table->foreignId('inventory_stocktake_id')->nullable()->constrained('inventory_stocktakes')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['warehouse_id', 'status', 'adjustment_date']);
            $table->index('adjustment_type');
            $table->index('inventory_stocktake_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
