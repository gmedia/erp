<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('approvable_type');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('conditions')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['approvable_type', 'is_active', 'code']);
        });

        Schema::create('approval_flow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_flow_id')->constrained('approval_flows')->cascadeOnDelete();
            $table->integer('step_order');
            $table->string('name');
            $table->enum('approver_type', ['user', 'role', 'department_head']);
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            // Using integer for approver_role_id since roles table might not exist
            $table->unsignedBigInteger('approver_role_id')->nullable();
            $table->foreignId('approver_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->enum('required_action', ['approve', 'review', 'acknowledge'])->default('approve');
            $table->integer('auto_approve_after_hours')->nullable();
            $table->integer('escalate_after_hours')->nullable();
            $table->foreignId('escalation_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('can_reject')->default(true);
            $table->timestamps();

            $table->unique(['approval_flow_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_flow_steps');
        Schema::dropIfExists('approval_flows');
    }
};
