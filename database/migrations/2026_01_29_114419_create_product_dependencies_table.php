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
        Schema::create('product_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade')
                ->comment('The dependent product (Product X)');
            $table->foreignId('required_product_id')
                ->constrained('products')
                ->onDelete('cascade')
                ->comment('The required product (Product A)');
            $table->enum('dependency_type', [
                'prerequisite',    // HARUS dibeli bersama, blocking
                'recommended',     // Recommended tapi tidak blocking
                'add_on',         // Add-on product
                'alternative'     // Alternative/substitute product
            ])->default('prerequisite');
            $table->integer('minimum_quantity')->default(1)
                ->comment('Minimum quantity of required product needed');
            $table->text('description')->nullable()
                ->comment('Explanation of the dependency');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['product_id', 'required_product_id', 'dependency_type'], 'product_deps_unique');
            $table->index('required_product_id');
            $table->index('dependency_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_dependencies');
    }
};
