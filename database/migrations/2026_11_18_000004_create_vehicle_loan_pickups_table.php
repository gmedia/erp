<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_loan_pickups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_loan_id')->unique()->constrained('vehicle_loans')->restrictOnDelete();
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->string('photo_mode');
            $table->string('selfie_photo_path');
            $table->string('selfie_photo_name');
            $table->foreignId('driver_snapshot_id')->nullable()->constrained('vehicle_drivers')->restrictOnDelete();
            $table->foreignId('unit_snapshot_id')->nullable()->constrained('vehicle_units')->restrictOnDelete();
            $table->timestamp('picked_at');
            $table->timestamp('key_handover_at')->nullable();
            $table->foreignId('security_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_loan_pickups');
    }
};
