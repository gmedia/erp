<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "2025"
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closed', 'locked'])->default('open');
            $table->timestamps();
        });

        Schema::create('coa_versions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "COA 2025 Standard"
            $table->foreignId('fiscal_year_id')->nullable()->constrained('fiscal_years')->nullOnDelete();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coa_version_id')->constrained('coa_versions')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('code')->index(); // Critical for version matching
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->string('sub_type')->nullable(); // e.g., current_asset
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->integer('level')->default(1);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            // Ensure unique code per version
            $table->unique(['coa_version_id', 'code']);
        });

        Schema::create('account_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('target_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->enum('type', ['merge', 'split', 'rename'])->default('rename');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_mappings');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('coa_versions');
        Schema::dropIfExists('fiscal_years');
    }
};
