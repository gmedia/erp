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
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade')
                ->comment('Finished product to be produced');
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->onDelete('set null');
            $table->decimal('quantity_to_produce', 15, 2);
            $table->date('production_date');
            $table->date('completion_date')->nullable();
            $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])
                ->default('draft');
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('production_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
