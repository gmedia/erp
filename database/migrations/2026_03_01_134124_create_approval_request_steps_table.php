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
        Schema::create('approval_request_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approval_flow_step_id')->constrained()->cascadeOnDelete();
            $table->integer('step_order');
            $table->enum('status', ['pending', 'approved', 'rejected', 'skipped'])->default('pending');
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('delegated_from')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action', ['approve', 'reject', 'skip', 'auto_approve'])->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamps();

            $table->unique(['approval_request_id', 'approval_flow_step_id'], 'app_req_step_req_id_flow_step_id_unique');
            $table->index('status');
            $table->index('due_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_request_steps');
    }
};
