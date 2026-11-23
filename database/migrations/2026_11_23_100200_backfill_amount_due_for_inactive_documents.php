<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['customer_invoices', 'supplier_bills'] as $table) {
            DB::table($table)
                ->whereIn('status', ['draft', 'cancelled', 'void'])
                ->where('amount_due', '!=', 0)
                ->update(['amount_due' => 0]);
        }
    }

    public function down(): void
    {
        // Nilai lama tidak bisa dipulihkan tanpa histori. Dibiarkan apa adanya.
    }
};
