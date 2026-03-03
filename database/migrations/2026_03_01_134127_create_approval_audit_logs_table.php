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
        Schema::create('approval_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->nullable()->constrained()->cascadeOnDelete();
            $table->morphs('approvable');
            $table->enum('event', [
                'submitted', 'step_approved', 'step_rejected', 'step_skipped', 
                'auto_approved', 'escalated', 'delegated', 'cancelled', 
                'resubmitted', 'completed'
            ]);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('step_order')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_audit_logs');
    }
};
