<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dimensi departemen (#114 fase 1). Nullable: data lama dan jurnal tanpa departemen
 * tetap sah dan tampil sebagai "Tanpa Departemen" di laporan.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'journal_entry_lines',
        'customer_invoices',
        'supplier_bills',
        'credit_notes',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint
                    ->foreignId('department_id')
                    ->nullable()
                    ->constrained('departments')
                    ->nullOnDelete();

                $blueprint->index('department_id', $table . '_department_id_index');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropForeign(['department_id']);
                $blueprint->dropIndex($table . '_department_id_index');
                $blueprint->dropColumn('department_id');
            });
        }
    }
};
