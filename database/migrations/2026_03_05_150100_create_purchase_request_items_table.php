<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('unit_id')->constrained('units');
            $table->decimal('quantity', 15, 2);
            $table->decimal('quantity_ordered', 15, 2)->default(0);
            $table->decimal('estimated_unit_price', 15, 2)->nullable();
            $table->decimal('estimated_total', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['purchase_request_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_items');
    }
};
