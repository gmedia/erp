<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_invoice_id')->constrained('customer_invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->string('description');
            $table->decimal('quantity', 15, 2)->default(1);
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('customer_invoice_id');
            $table->index('product_id');
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_invoice_items');
    }
};
