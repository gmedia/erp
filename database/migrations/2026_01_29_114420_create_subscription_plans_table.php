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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade');
            $table->string('name')
                ->comment('Plan name: Monthly, Quarterly, Annual');
            $table->string('code')->unique()
                ->comment('Unique plan code: PRD-001-MONTHLY');
            $table->text('description')->nullable();
            
            // Billing cycle
            $table->enum('billing_interval', ['daily', 'weekly', 'monthly', 'quarterly', 'semi_annual', 'annual', 'biennial'])
                ->comment('How often customer is billed');
            $table->integer('billing_interval_count')->default(1)
                ->comment('Multiplier for interval (e.g., 3 months = interval:monthly, count:3)');
            
            // Pricing
            $table->decimal('price', 15, 2)
                ->comment('Recurring price per billing cycle');
            $table->decimal('setup_fee', 15, 2)->default(0)
                ->comment('One-time setup/activation fee');
            
            // Trial
            $table->integer('trial_period_days')->nullable()
                ->comment('Free trial days (overrides product setting)');
            
            // Contract
            $table->integer('minimum_commitment_cycles')->nullable()
                ->comment('Minimum billing cycles required (null = no minimum)');
            $table->boolean('auto_renew')->default(true)
                ->comment('Auto-renew at end of commitment');
            
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->timestamps();
            
            $table->index(['product_id', 'billing_interval']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
