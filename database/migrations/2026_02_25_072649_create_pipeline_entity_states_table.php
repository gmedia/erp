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
        Schema::create('pipeline_entity_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained('pipelines')->onDelete('cascade');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('current_state_id')->constrained('pipeline_states')->onDelete('restrict');
            $table->foreignId('last_transitioned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('last_transitioned_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['pipeline_id', 'entity_type', 'entity_id'], 'pipeline_entity_states_unique');
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pipeline_entity_states');
    }
};
