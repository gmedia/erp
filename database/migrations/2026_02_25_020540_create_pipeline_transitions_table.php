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
        Schema::create('pipeline_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_state_id')->constrained('pipeline_states')->cascadeOnDelete();
            $table->foreignId('to_state_id')->constrained('pipeline_states')->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->string('required_permission')->nullable();
            $table->json('guard_conditions')->nullable();
            $table->boolean('requires_confirmation')->default(false);
            $table->boolean('requires_comment')->default(false);
            $table->boolean('requires_approval')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['pipeline_id', 'from_state_id', 'to_state_id'], 'pipeline_transitions_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pipeline_transitions');
    }
};
