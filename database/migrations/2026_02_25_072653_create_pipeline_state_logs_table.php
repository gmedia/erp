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
        Schema::create('pipeline_state_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_entity_state_id')->constrained('pipeline_entity_states')->onDelete('cascade');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('from_state_id')->nullable()->constrained('pipeline_states')->onDelete('set null');
            $table->foreignId('to_state_id')->constrained('pipeline_states')->onDelete('cascade');
            $table->foreignId('transition_id')->nullable()->constrained('pipeline_transitions')->onDelete('set null');
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['entity_type', 'entity_id']);
            $table->index('performed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pipeline_state_logs');
    }
};
