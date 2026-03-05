<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->nullable()->unique();
            $table->foreignId('from_warehouse_id')->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->constrained('warehouses');
            $table->date('transfer_date');
            $table->date('expected_arrival_date')->nullable();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'in_transit', 'received', 'cancelled'])
                ->default('draft');
            $table->text('notes')->nullable();

            $table->foreignId('requested_by')->nullable()->constrained('employees');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('shipped_by')->nullable()->constrained('users');
            $table->timestamp('shipped_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->timestamp('received_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');

            $table->timestamps();

            $table->index('status');
            $table->index('transfer_date');
            $table->index(['from_warehouse_id', 'to_warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
