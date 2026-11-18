<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_loans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->text('purpose');
            $table->foreignId('driver_id')->nullable()->constrained('vehicle_drivers')->restrictOnDelete();
            $table->foreignId('vehicle_unit_id')->nullable()->constrained('vehicle_units')->restrictOnDelete();
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'submitted_at']);
            $table->index(['requester_id', 'status']);
            $table->index(['start_at', 'end_at']);
            $table->index(['branch_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_loans');
    }
};
