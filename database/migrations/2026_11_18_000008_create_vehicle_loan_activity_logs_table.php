<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_loan_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_loan_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['vehicle_loan_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_loan_activity_logs');
    }
};
