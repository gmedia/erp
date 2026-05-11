<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('journal_type', 20)->default('general')->after('status');
            $table->string('source_type')->nullable()->after('journal_type');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');

            $table->index('journal_type');
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex(['journal_type']);
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropColumn(['journal_type', 'source_type', 'source_id']);
        });
    }
};
