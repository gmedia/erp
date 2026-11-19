<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_loan_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_id')->unique()->constrained('vehicle_loan_pickups')->restrictOnDelete();
            $table->foreignId('vehicle_loan_id')->constrained('vehicle_loans')->restrictOnDelete();
            $table->foreignId('returner_id')->constrained('users')->restrictOnDelete();
            $table->string('handover_photo_path');
            $table->string('handover_photo_name');
            $table->string('condition_photo_path');
            $table->string('condition_photo_name');
            $table->boolean('incident')->default(false);
            $table->text('incident_note')->nullable();
            $table->unsignedInteger('odometer_start')->nullable();
            $table->unsignedInteger('odometer_end')->nullable();
            $table->timestamp('returned_at');
            $table->foreignId('received_by_security_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_loan_returns');
    }
};
