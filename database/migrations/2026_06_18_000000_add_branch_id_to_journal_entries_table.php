<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table
                ->foreignId('branch_id')
                ->nullable()
                ->after('journal_type')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->index(
                ['fiscal_year_id', 'status', 'branch_id'],
                'journal_entries_fy_status_branch_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex('journal_entries_fy_status_branch_index');
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
