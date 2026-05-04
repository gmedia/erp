<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ap_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ap_payment_id')->constrained('ap_payments')->cascadeOnDelete();
            $table->foreignId('supplier_bill_id')->constrained('supplier_bills')->cascadeOnDelete();
            $table->decimal('allocated_amount', 15, 2);
            $table->decimal('discount_taken', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['ap_payment_id', 'supplier_bill_id'], 'ap_alloc_payment_bill_unique');
            $table->index('ap_payment_id');
            $table->index('supplier_bill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_payment_allocations');
    }
};
