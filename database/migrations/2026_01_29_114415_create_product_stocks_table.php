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
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->integer('quantity_on_hand')->default(0)
                ->comment('Current physical stock');
            $table->integer('quantity_reserved')->default(0)
                ->comment('Reserved for production orders or sales');
            $table->integer('minimum_quantity')->default(0)
                ->comment('Reorder point');
            $table->decimal('average_cost', 15, 2)->default(0)
                ->comment('Weighted average cost for COGS calculation');
            $table->timestamps();
            
            $table->unique(['product_id', 'branch_id']);
            $table->index('quantity_on_hand');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};
