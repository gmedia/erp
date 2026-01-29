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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Product/Service code (SKU)');
            $table->string('name');
            $table->text('description')->nullable();
            
            // Product Type
            $table->enum('type', [
                'raw_material',      // Bahan baku yang dibeli
                'work_in_progress',  // Barang setengah jadi
                'finished_good',     // Barang jadi hasil produksi
                'purchased_good',    // Barang jadi yang dibeli (untuk trading)
                'service'            // Jasa
            ])->default('finished_good');
            
            // Relations
            $table->foreignId('category_id')->constrained('product_categories')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Pricing & Costing
            $table->decimal('cost', 15, 2)->default(0)
                ->comment('Production/purchase cost per unit');
            $table->decimal('selling_price', 15, 2)->default(0)
                ->comment('Default selling price');
            $table->decimal('markup_percentage', 5, 2)->nullable()
                ->comment('Markup % over cost');
            
            // Billing Model
            $table->enum('billing_model', ['one_time', 'subscription', 'both'])
                ->default('one_time')
                ->comment('How this product is billed');
            $table->boolean('is_recurring')->default(false)
                ->comment('TRUE if this is a subscription product');
            $table->integer('trial_period_days')->nullable()
                ->comment('Free trial period in days (null = no trial)');
            $table->boolean('allow_one_time_purchase')->default(true)
                ->comment('Allow buying without subscription');
            
            // Manufacturing Flags
            $table->boolean('is_manufactured')->default(false)
                ->comment('TRUE if this product is manufactured (has BOM)');
            $table->boolean('is_purchasable')->default(true)
                ->comment('TRUE if can be purchased from suppliers');
            $table->boolean('is_sellable')->default(true)
                ->comment('TRUE if can be sold to customers');
            
            // General
            $table->boolean('is_taxable')->default(true);
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('category_id');
            $table->index('type');
            $table->index('status');
            $table->index('is_manufactured');
            $table->index('billing_model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
