<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_app_releases', function (Blueprint $table) {
            $table->id();
            $table->string('version_name', 50);
            $table->unsignedBigInteger('version_code')->unique();
            $table->unsignedBigInteger('min_supported_version_code');
            $table->text('notes')->nullable();
            $table->string('file_path');
            $table->unsignedBigInteger('file_size');
            $table->string('sha256', 64);
            $table->boolean('is_active')->default(false);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_app_releases');
    }
};
