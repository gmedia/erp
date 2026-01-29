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
        Schema::create('bill_of_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finished_product_id')
                ->constrained('products')
                ->onDelete('cascade')
                ->comment('The finished product being manufactured');
            $table->foreignId('raw_material_id')
                ->constrained('products')
                ->onDelete('cascade')
                ->comment('The raw material/component needed');
            $table->decimal('quantity_required', 15, 4)
                ->comment('Quantity of raw material needed per 1 unit of finished product');
            $table->foreignId('unit_id')
                ->constrained('units')
                ->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['finished_product_id', 'raw_material_id']);
            $table->index('raw_material_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_of_materials');
    }
};
