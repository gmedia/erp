<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('journal_entry_lines', 'branch_id')) {
            return;
        }

        DB::table('journal_entries')
            ->whereNotNull('branch_id')
            ->orderBy('id')
            ->chunkById(500, function ($entries): void {
                $byBranch = collect($entries)->groupBy('branch_id');

                foreach ($byBranch as $branchId => $group) {
                    DB::table('journal_entry_lines')
                        ->whereIn('journal_entry_id', collect($group)->pluck('id'))
                        ->whereNull('branch_id')
                        ->update(['branch_id' => $branchId]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('journal_entry_lines', 'branch_id')) {
            return;
        }

        DB::table('journal_entry_lines')->update(['branch_id' => null]);
    }
};
