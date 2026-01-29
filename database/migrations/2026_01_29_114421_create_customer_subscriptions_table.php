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
        Schema::create('customer_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscription_number')->unique()
                ->comment('Unique subscription reference');
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->onDelete('cascade');
            $table->foreignId('subscription_plan_id')
                ->constrained('subscription_plans')
                ->onDelete('restrict');
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('restrict');
            
            // Subscription lifecycle
            $table->enum('status', [
                'trial',           // In trial period
                'active',          // Active and paid
                'past_due',        // Payment failed, grace period
                'suspended',       // Temporarily suspended
                'cancelled',       // Cancelled by customer
                'expired'          // Contract ended
            ])->default('trial');
            
            // Dates
            $table->date('trial_start_date')->nullable();
            $table->date('trial_end_date')->nullable();
            $table->date('start_date')
                ->comment('Subscription start date (after trial)');
            $table->date('current_period_start')
                ->comment('Current billing period start');
            $table->date('current_period_end')
                ->comment('Current billing period end');
            $table->date('cancellation_date')->nullable();
            $table->date('cancellation_effective_date')->nullable()
                ->comment('When cancellation takes effect');
            
            // Billing
            $table->integer('billing_cycles_completed')->default(0);
            $table->boolean('auto_renew')->default(true);
            $table->decimal('recurring_amount', 15, 2)
                ->comment('Amount charged per cycle');
            
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('customer_id');
            $table->index('status');
            $table->index('current_period_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_subscriptions');
    }
};
