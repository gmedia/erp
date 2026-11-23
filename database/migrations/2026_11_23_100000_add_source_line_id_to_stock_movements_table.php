<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('source_line_id')->nullable()->after('reference_id');
            $table->index(['reference_type', 'reference_id', 'source_line_id'], 'stock_movements_source_line_index');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_source_line_index');
            $table->dropColumn('source_line_id');
        });
    }
};
