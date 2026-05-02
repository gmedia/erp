<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ar_receipt_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ar_receipt_id')->constrained('ar_receipts')->cascadeOnDelete();
            $table->foreignId('customer_invoice_id')->constrained('customer_invoices')->cascadeOnDelete();
            $table->decimal('allocated_amount', 15, 2)->default(0);
            $table->decimal('discount_given', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['ar_receipt_id', 'customer_invoice_id'], 'ar_alloc_receipt_invoice_unique');
            $table->index('ar_receipt_id');
            $table->index('customer_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_receipt_allocations');
    }
};
