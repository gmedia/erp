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
        Schema::create('subscription_billing_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_subscription_id')
                ->constrained('customer_subscriptions')
                ->onDelete('cascade');
            $table->string('invoice_number')->unique();
            
            // Billing period
            $table->date('period_start');
            $table->date('period_end');
            $table->date('billing_date')
                ->comment('When invoice was generated');
            $table->date('due_date')
                ->comment('Payment due date');
            
            // Amounts
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            
            // Status
            $table->enum('status', [
                'draft',           // Not yet sent
                'pending',         // Sent, awaiting payment
                'paid',            // Fully paid
                'partially_paid',  // Partial payment received
                'overdue',         // Past due date
                'cancelled',       // Cancelled
                'refunded'         // Refunded
            ])->default('pending');
            
            $table->date('paid_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            
            $table->integer('retry_count')->default(0)
                ->comment('Number of payment retry attempts');
            $table->date('next_retry_date')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('customer_subscription_id');
            $table->index('status');
            $table->index('due_date');
            $table->index('billing_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_billing_records');
    }
};
