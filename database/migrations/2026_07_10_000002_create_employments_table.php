<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('salary', 15, 2)->nullable();
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->string('employment_status')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->index('employee_id');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employments');
    }
};
