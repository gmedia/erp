<?php

use Database\Seeders\ReportConfigurationSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('report_sections', 'account_codes')) {
            Schema::table('report_sections', function (Blueprint $table) {
                $table->json('account_codes')->nullable()->after('account_sub_type_filter');
            });
        }

        // Instalasi yang sudah berjalan: segarkan konfigurasi laporan yang tersimpan
        // agar section granular punya pemetaan account_codes. Instalasi baru belum
        // punya baris konfigurasi pada tahap ini (diisi db:seed), jadi dilewati.
        if (Schema::hasTable('report_configurations') && DB::table('report_configurations')->exists()) {
            (new ReportConfigurationSeeder)->run();
        }
    }

    public function down(): void
    {
        Schema::table('report_sections', function (Blueprint $table) {
            $table->dropColumn('account_codes');
        });
    }
};
