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
        Schema::table('warehouses', function (Blueprint $table) {
            $table
                ->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->restrictOnDelete();
            $table->string('code', 50)->nullable();
            $table->unique(['branch_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'code']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['branch_id', 'code']);
        });
    }
};
