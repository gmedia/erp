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
        Schema::create('pipeline_transition_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_transition_id')->constrained()->cascadeOnDelete();
            $table->enum('action_type', ['update_field', 'create_record', 'send_notification', 'dispatch_job', 'trigger_approval', 'webhook', 'custom']);
            $table->integer('execution_order');
            $table->json('config');
            $table->boolean('is_async')->default(false);
            $table->enum('on_failure', ['abort', 'continue', 'log_and_continue'])->default('abort');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['pipeline_transition_id', 'execution_order'], 'pipeline_transition_actions_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pipeline_transition_actions');
    }
};
