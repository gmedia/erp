<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            $table
                ->foreignId('branch_id')
                ->nullable()
                ->after('approvable_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->index(
                ['branch_id', 'status'],
                'approval_requests_branch_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->dropIndex('approval_requests_branch_status_index');
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
