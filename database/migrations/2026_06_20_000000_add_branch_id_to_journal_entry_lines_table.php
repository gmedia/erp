<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table
                ->foreignId('branch_id')
                ->nullable()
                ->after('account_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->index(
                ['branch_id', 'account_id'],
                'journal_entry_lines_branch_account_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex('journal_entry_lines_branch_account_index');
            $table->dropColumn('branch_id');
        });
    }
};
