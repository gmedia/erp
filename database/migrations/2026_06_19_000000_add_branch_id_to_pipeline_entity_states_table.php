<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pipeline_entity_states', function (Blueprint $table) {
            $table
                ->foreignId('branch_id')
                ->nullable()
                ->after('entity_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->index(
                ['branch_id', 'current_state_id'],
                'pipeline_entity_states_branch_state_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('pipeline_entity_states', function (Blueprint $table) {
            $table->dropIndex('pipeline_entity_states_branch_state_index');
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
