<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modul: Manajemen Aset (Fixed Assets & Asset Tracking)
     *
     * Tujuan desain:
     * - Menyimpan master data aset (nilai perolehan, lokasi, PIC, status).
     * - Menyimpan audit trail pergerakan aset (transfer/assign/return/dispose).
     * - Mendukung maintenance dan stocktake (inventarisasi berkala).
     * - Mendukung proses depresiasi periodik dan link ke jurnal akuntansi.
     *
     * Contoh kasus yang didukung:
     * 1) Pembelian aset: buat record di `assets` + log awal di `asset_movements` (movement_type = acquired).
     * 2) Mutasi aset: pindah cabang/ruang/PIC, update kolom “current state” di `assets`,
     *    lalu simpan histori di `asset_movements` (movement_type = transfer/assign/return).
     * 3) Stocktake: buat `asset_stocktakes`, lalu `asset_stocktake_items` untuk hasil cek per aset
     *    (found/missing/damaged/moved) termasuk lokasi expected vs found.
     * 4) Depresiasi bulanan: buat `asset_depreciation_runs` (periode), generate `asset_depreciation_lines`
     *    per aset, lalu (opsional) posting ke `journal_entries` dan simpan `journal_entry_id`.
     *
     * Catatan teknis:
     * - Beberapa unique constraint diberi nama manual agar aman di MariaDB (limit panjang nama index).
     */
    public function up(): void
    {
        // ==============================
        // Master: Kategori & Model Aset
        // ==============================
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->integer('useful_life_months_default')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_category_id')->constrained('asset_categories')->cascadeOnDelete();
            $table->string('manufacturer')->nullable();
            $table->string('model_name');
            $table->json('specs')->nullable();
            $table->timestamps();

            $table->index('asset_category_id');
        });

        // ==============================
        // Master: Lokasi Aset (hierarki)
        // ==============================
        // Lokasi ini melengkapi `branches` (Cabang). Contoh hierarki: Cabang -> Gedung -> Lantai -> Ruang.
        Schema::create('asset_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('asset_locations')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->timestamps();

            // Kode lokasi unik dalam satu cabang.
            $table->unique(['branch_id', 'code'], 'asset_locations_branch_code_unique');
            $table->index('parent_id');
            $table->index('branch_id');
        });

        // ==============================
        // Master: Aset (current state)
        // ==============================
        // Kolom lokasi/department/employee di sini merepresentasikan kondisi terkini.
        // Riwayat perubahan disimpan di `asset_movements`.
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->unique();
            $table->string('name');
            $table->foreignId('asset_model_id')->nullable()->constrained('asset_models')->nullOnDelete();
            $table->foreignId('asset_category_id')->constrained('asset_categories')->restrictOnDelete();
            $table->string('serial_number')->nullable();
            $table->string('barcode')->nullable()->unique();

            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('asset_location_id')->nullable()->constrained('asset_locations')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();

            $table->date('purchase_date');
            $table->decimal('purchase_cost', 15, 2);
            $table->string('currency', 3)->default('IDR');
            $table->date('warranty_end_date')->nullable();

            $table->enum('status', ['draft', 'active', 'maintenance', 'disposed', 'lost'])->default('draft');
            $table->enum('condition', ['good', 'needs_repair', 'damaged'])->nullable();
            $table->text('notes')->nullable();

            // Depresiasi:
            // - Jika akun depresiasi diset per aset, isi account_id di bawah.
            // - Nilai accumulated/book_value disimpan sebagai cache (bisa dihitung ulang dari depreciation lines).
            $table->enum('depreciation_method', ['straight_line', 'declining_balance'])->default('straight_line');
            $table->date('depreciation_start_date')->nullable();
            $table->integer('useful_life_months')->nullable();
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->decimal('book_value', 15, 2)->default(0);
            $table->foreignId('depreciation_expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('accumulated_depr_account_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->timestamps();

            $table->index('asset_category_id');
            $table->index('asset_model_id');
            $table->index('branch_id');
            $table->index('asset_location_id');
            $table->index('department_id');
            $table->index('employee_id');
            $table->index('supplier_id');
            $table->index('status');
            $table->index('serial_number');
        });

        // ==============================
        // Audit Trail: Pergerakan Aset
        // ==============================
        // Semua transfer/assign/return/dispose dicatat di sini untuk kebutuhan audit.
        Schema::create('asset_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->enum('movement_type', ['acquired', 'transfer', 'assign', 'return', 'dispose', 'adjustment']);
            $table->timestamp('moved_at');

            $table->foreignId('from_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('to_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('asset_locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('asset_locations')->nullOnDelete();
            $table->foreignId('from_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('to_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('from_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('to_employee_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['asset_id', 'moved_at']);
            $table->index('movement_type');
            $table->index('to_branch_id');
            $table->index('to_employee_id');
        });

        // ==============================
        // Maintenance / Servis Aset
        // ==============================
        // Catatan biaya maintenance bisa dipakai untuk analisis (TCO) dan histori kerusakan.
        Schema::create('asset_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->enum('maintenance_type', ['preventive', 'corrective', 'calibration', 'other'])->default('other');
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('performed_at')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->decimal('cost', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('asset_id');
            $table->index('status');
            $table->index('maintenance_type');
        });

        // ==============================
        // Stocktake (Inventarisasi)
        // ==============================
        // 1 record stocktake bisa memiliki banyak item hasil cek aset.
        Schema::create('asset_stocktakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('reference');
            $table->timestamp('planned_at');
            $table->timestamp('performed_at')->nullable();
            $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['branch_id', 'reference'], 'asset_stocktakes_branch_ref_unique');
            $table->index('branch_id');
            $table->index('status');
        });

        // Item hasil cek stocktake.
        // expected_* adalah lokasi/cabang yang seharusnya, found_* adalah yang ditemukan di lapangan.
        Schema::create('asset_stocktake_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_stocktake_id')->constrained('asset_stocktakes')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->restrictOnDelete();

            $table->foreignId('expected_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('expected_location_id')->nullable()->constrained('asset_locations')->nullOnDelete();
            $table->foreignId('found_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('found_location_id')->nullable()->constrained('asset_locations')->nullOnDelete();

            $table->enum('result', ['found', 'missing', 'damaged', 'moved']);
            $table->text('notes')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['asset_stocktake_id', 'asset_id'], 'asset_stocktake_items_unique');
            $table->index('asset_id');
            $table->index('result');
        });

        // ==============================
        // Depresiasi: Header Periode
        // ==============================
        // Satu periode depresiasi (mis. 1 bulan) disimpan sebagai satu "run".
        // Jika sudah diposting, `journal_entry_id` akan menunjuk jurnal yang dibuat.
        Schema::create('asset_depreciation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('status', ['draft', 'calculated', 'posted', 'void'])->default('draft');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['fiscal_year_id', 'period_start', 'period_end'], 'asset_depr_runs_period_unique');
            $table->index('status');
            $table->index('fiscal_year_id');
        });

        // Depresiasi: detail per aset untuk satu periode/run.
        // Constraint unik memastikan satu aset hanya punya 1 baris depresiasi per run.
        Schema::create('asset_depreciation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_depreciation_run_id')->constrained('asset_depreciation_runs')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('accumulated_before', 15, 2);
            $table->decimal('accumulated_after', 15, 2);
            $table->decimal('book_value_after', 15, 2);
            $table->timestamps();

            $table->unique(['asset_depreciation_run_id', 'asset_id'], 'asset_depr_lines_run_asset_unique');
            $table->index('asset_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_depreciation_lines');
        Schema::dropIfExists('asset_depreciation_runs');
        Schema::dropIfExists('asset_stocktake_items');
        Schema::dropIfExists('asset_stocktakes');
        Schema::dropIfExists('asset_maintenances');
        Schema::dropIfExists('asset_movements');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_locations');
        Schema::dropIfExists('asset_models');
        Schema::dropIfExists('asset_categories');
    }
};
