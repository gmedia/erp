<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_configuration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('report_sections')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('section_type', 20);
            $table->string('account_type_filter', 30)->nullable();
            $table->string('account_sub_type_filter', 50)->nullable();
            $table->string('sign_convention', 20)->default('normal');
            $table->string('formula')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['report_configuration_id', 'code'], 'report_sections_config_code_unique');
            $table->index(['report_configuration_id', 'sort_order']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_sections');
    }
};
