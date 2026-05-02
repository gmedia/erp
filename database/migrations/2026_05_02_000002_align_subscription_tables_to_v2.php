<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('subscription_billing_records');
        Schema::dropIfExists('customer_subscriptions');
        Schema::dropIfExists('subscription_plans');

        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('name');
            $table->enum('billing_interval', [
                'daily',
                'weekly',
                'monthly',
                'quarterly',
                'semi_annual',
                'annual',
            ]);
            $table->decimal('price', 15, 2);
            $table->decimal('setup_fee', 15, 2)->default(0);
            $table->integer('trial_period_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('product_id');
            $table->index('is_active');
        });

        Schema::create('customer_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->onDelete('restrict');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_billing_date');
            $table->enum('status', ['trial', 'active', 'past_due', 'cancelled', 'expired'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('subscription_plan_id');
            $table->index('status');
            $table->index('next_billing_date');
        });

        Schema::create('subscription_billing_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_subscription_id')->constrained('customer_subscriptions')->onDelete('cascade');
            $table->date('billing_period_start');
            $table->date('billing_period_end');
            $table->decimal('amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('customer_subscription_id');
            $table->index('status');
            $table->index(['billing_period_start', 'billing_period_end'], 'billing_records_period_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_billing_records');
        Schema::dropIfExists('customer_subscriptions');
        Schema::dropIfExists('subscription_plans');

        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('name')->comment('Plan name: Monthly, Quarterly, Annual');
            $table->string('code')->unique()->comment('Unique plan code: PRD-001-MONTHLY');
            $table->text('description')->nullable();
            $table->enum('billing_interval', [
                'daily',
                'weekly',
                'monthly',
                'quarterly',
                'semi_annual',
                'annual',
                'biennial',
            ])->comment('How often customer is billed');
            $table->integer('billing_interval_count')->default(1)
                ->comment('Multiplier for interval (e.g., 3 months = interval:monthly, count:3)');
            $table->decimal('price', 15, 2)->comment('Recurring price per billing cycle');
            $table->decimal('setup_fee', 15, 2)->default(0)->comment('One-time setup/activation fee');
            $table->integer('trial_period_days')->nullable()
                ->comment('Free trial days (overrides product setting)');
            $table->integer('minimum_commitment_cycles')->nullable()
                ->comment('Minimum billing cycles required (null = no minimum)');
            $table->boolean('auto_renew')->default(true)->comment('Auto-renew at end of commitment');
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->timestamps();

            $table->index(['product_id', 'billing_interval']);
            $table->index('status');
        });

        Schema::create('customer_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscription_number')->unique()->comment('Unique subscription reference');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->onDelete('restrict');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->enum('status', [
                'trial',
                'active',
                'past_due',
                'suspended',
                'cancelled',
                'expired',
            ])->default('trial');
            $table->date('trial_start_date')->nullable();
            $table->date('trial_end_date')->nullable();
            $table->date('start_date')->comment('Subscription start date (after trial)');
            $table->date('current_period_start')->comment('Current billing period start');
            $table->date('current_period_end')->comment('Current billing period end');
            $table->date('cancellation_date')->nullable();
            $table->date('cancellation_effective_date')->nullable()
                ->comment('When cancellation takes effect');
            $table->integer('billing_cycles_completed')->default(0);
            $table->boolean('auto_renew')->default(true);
            $table->decimal('recurring_amount', 15, 2)->comment('Amount charged per cycle');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('status');
            $table->index('current_period_end');
        });

        Schema::create('subscription_billing_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_subscription_id')
                ->constrained('customer_subscriptions')->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->date('billing_date')->comment('When invoice was generated');
            $table->date('due_date')->comment('Payment due date');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->enum('status', [
                'draft',
                'pending',
                'paid',
                'partially_paid',
                'overdue',
                'cancelled',
                'refunded',
            ])->default('pending');
            $table->date('paid_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->integer('retry_count')->default(0)->comment('Number of payment retry attempts');
            $table->date('next_retry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('customer_subscription_id');
            $table->index('status');
            $table->index('due_date');
            $table->index('billing_date');
        });
    }
};
