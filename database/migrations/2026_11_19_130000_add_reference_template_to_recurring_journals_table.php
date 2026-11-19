<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_journals', function (Blueprint $table) {
            $table->string('reference_template')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('recurring_journals', function (Blueprint $table) {
            $table->dropColumn('reference_template');
        });
    }
};
