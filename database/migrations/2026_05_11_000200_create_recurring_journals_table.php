<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_journals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('fiscal_year_id')->nullable()->constrained()->nullOnDelete();
            $table->string('frequency', 20);
            $table->date('next_run_date');
            $table->date('last_run_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->boolean('auto_post')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('is_active');
            $table->index('next_run_date');
            $table->index('frequency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_journals');
    }
};
