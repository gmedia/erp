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
        // ==========================================
        // Fiscal Years - Periode Akuntansi
        // ==========================================
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "2025"
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closed', 'locked'])->default('open');
            $table->timestamps();

            $table->index('status');
        });

        // ==========================================
        // COA Versions - Versi Chart of Accounts
        // ==========================================
        Schema::create('coa_versions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "COA 2025 Standard"
            $table->foreignId('fiscal_year_id')->nullable()->constrained('fiscal_years')->nullOnDelete();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->timestamps();

            $table->index('status');
            $table->index('fiscal_year_id');
        });

        // ==========================================
        // Accounts - Daftar Akun (Master COA)
        // ==========================================
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coa_version_id')->constrained('coa_versions')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('code')->index(); // Kunci utama untuk perbandingan antar versi
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->string('sub_type')->nullable(); // e.g., current_asset, non_current_asset
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->integer('level')->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_cash_flow')->default(false); // Untuk laporan arus kas
            $table->text('description')->nullable();
            $table->timestamps();

            // Ensure unique code per version
            $table->unique(['coa_version_id', 'code']);
            $table->index('parent_id');
            $table->index('type');
        });

        // ==========================================
        // Account Mappings - Pemetaan Akun Antar Versi
        // ==========================================
        Schema::create('account_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('target_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->enum('type', ['merge', 'split', 'rename'])->default('rename');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['source_account_id', 'target_account_id']);
        });

        // ==========================================
        // Journal Entries - Header Jurnal Umum
        // ==========================================
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->cascadeOnDelete();
            $table->string('entry_number')->unique(); // e.g., JV-2026-00001
            $table->date('entry_date');
            $table->string('reference')->nullable(); // No. bukti eksternal
            $table->string('description');
            $table->enum('status', ['draft', 'posted', 'void'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index('entry_date');
            $table->index('status');
            $table->index('fiscal_year_id');
        });

        // ==========================================
        // Journal Entry Lines - Detail Jurnal (Debit/Kredit)
        // ==========================================
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('memo')->nullable();
            $table->timestamps();

            $table->index('journal_entry_id');
            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('account_mappings');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('coa_versions');
        Schema::dropIfExists('fiscal_years');
    }
};
