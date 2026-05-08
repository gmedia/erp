<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ap_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->nullable()->unique();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years');
            $table->date('payment_date');
            $table->enum('payment_method', [
                'bank_transfer',
                'cash',
                'check',
                'giro',
                'other',
            ])->default('bank_transfer');
            $table->foreignId('bank_account_id')->constrained('accounts');
            $table->string('currency', 3)->default('IDR');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('total_allocated', 15, 2)->default(0);
            $table->decimal('total_unallocated', 15, 2)->default(0);
            $table->string('reference')->nullable();
            $table->enum('status', [
                'draft',
                'pending_approval',
                'confirmed',
                'reconciled',
                'cancelled',
                'void',
            ])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index('supplier_id');
            $table->index('status');
            $table->index('payment_date');
            $table->index('fiscal_year_id');
            $table->index('bank_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_payments');
    }
};
