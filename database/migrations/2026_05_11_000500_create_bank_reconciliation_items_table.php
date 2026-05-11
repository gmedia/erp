<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_reconciliation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_entry_line_id')->nullable()->constrained()->nullOnDelete();
            $table->date('transaction_date');
            $table->string('description');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('type', 30);
            $table->boolean('is_reconciled')->default(false);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('bank_reconciliation_id', 'bank_recon_items_recon_id_index');
            $table->index('journal_entry_line_id', 'bank_recon_items_jel_id_index');
            $table->index('type');
            $table->index('is_reconciled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_items');
    }
};
