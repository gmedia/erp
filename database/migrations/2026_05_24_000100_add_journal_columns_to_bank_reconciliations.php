<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_reconciliations', function (Blueprint $table) {
            $table->foreignId('journal_entry_id')->nullable()->after('notes')->constrained('journal_entries')->nullOnDelete();
        });

        Schema::table('bank_reconciliation_items', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('journal_entry_line_id')->constrained('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bank_reconciliations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_entry_id');
        });

        Schema::table('bank_reconciliation_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
        });
    }
};
