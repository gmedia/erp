<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('production_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')
                ->constrained('production_orders')
                ->onDelete('cascade');
            $table->foreignId('raw_material_id')
                ->constrained('products')
                ->onDelete('cascade');
            $table->decimal('quantity_used', 15, 4);
            $table->decimal('unit_cost', 15, 2)
                ->comment('Cost per unit at time of production');
            $table->decimal('total_cost', 15, 2)
                ->comment('quantity_used * unit_cost');
            $table->timestamps();
            
            $table->index('production_order_id');
            $table->index('raw_material_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_order_items');
    }
};
