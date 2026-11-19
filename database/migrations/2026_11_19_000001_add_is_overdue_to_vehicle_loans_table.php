<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_loans', function (Blueprint $table) {
            $table->boolean('is_overdue')->default(false)->after('status');
            $table->index(['is_overdue', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_loans', function (Blueprint $table) {
            $table->dropIndex(['is_overdue', 'status']);
            $table->dropColumn('is_overdue');
        });
    }
};
