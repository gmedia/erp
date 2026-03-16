<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number')->nullable()->unique();
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('request_date');
            $table->date('required_date')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'rejected',
                'partially_ordered',
                'fully_ordered',
                'cancelled',
            ])->default('draft');
            $table->decimal('estimated_amount', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('branch_id');
            $table->index('request_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
