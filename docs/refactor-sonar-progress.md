# Refactor Sonar Progress

Dokumen ini menyimpan status batch refactor berbasis Sonar agar prompt tetap stabil dan tidak cepat usang.

## Cara Pakai

1. Update status batch di file ini setiap selesai PR.
2. Pertahankan satu batch aktif per PR.
3. Simpan evidence singkat: metrik sebelum/sesudah + link PR.

## Batch Tracker

| Batch | Status | Scope Modul | Prioritas Teknis | Evidence |
|------|------|------|------|------|
| A | done | purchase-history-report, purchase-order-status-report, goods-receipt-report, stock-movement-report, stock-adjustment-report, inventory-valuation-report, inventory-stocktake-variance-report | report requests/resources | n/a |
| B | done | purchase-requests, purchase-orders, supplier-returns, goods-receipts, stock-adjustments, stock-movements, stock-transfers, inventory-stocktakes | filter services + item controllers | n/a |
| C | in-progress | assets, products, asset-movements, asset-maintenances, asset-stocktakes | AssetFilterService, ProductFilterService, item controllers | baseline 2026-03-30; quality gate: ERROR (new_duplicated_lines_density 11.6 > 3.0) |
| D | next | financial-reporting | FinancialReportService + query/mapping laporan keuangan | pending |
| E | next | account-mappings, journal-entries, goods-receipts, purchase-requests | pasangan Store*Request/Update*Request | pending |

## Baseline Metrics

Isi saat mulai batch baru.

- duplicated_lines: 6620
- duplicated_blocks: 373
- duplicated_lines_density: 7.5
- ncloc: 73194
- coverage: 86.5

## Delta Metrics (setelah batch)

Isi setelah batch selesai dan sebelum merge.

- duplicated_lines: 0 (6620 -> 6620, interim)
- duplicated_blocks: 0 (373 -> 373, interim)
- duplicated_lines_density: 0.0 (7.5 -> 7.5, interim)
- ncloc: 0 (73194 -> 73194, interim)
- coverage: 0.0 (86.5 -> 86.5, interim)

## Snapshot Analisa Sonar (2026-03-30)

- Quality Gate: ERROR
- Gate blocker utama: new_duplicated_lines_density = 11.6 (threshold: 3.0)
- Catatan: metrik global belum berubah dari baseline, sehingga fokus penurunan duplikasi perlu diarahkan ke file new code dan file dengan blok duplikasi terbesar.

### Prioritas Duplikasi Backend (Batch C)

- app/Actions/AssetStocktakes/IndexAssetStocktakeVarianceAction.php <-> app/Actions/AssetStocktakes/ExportAssetStocktakeVariancesAction.php: blok duplikasi 68 baris.
- app/Domain/AssetMaintenances/AssetMaintenanceFilterService.php <-> app/Domain/AssetMovements/AssetMovementFilterService.php: blok duplikasi filter/search lintas modul.
- app/Domain/Assets/AssetFilterService.php <-> app/Domain/AssetStocktakes/AssetStocktakeFilterService.php: duplikasi pola sorting/join branch.
- app/Actions/Assets/IndexAssetsAction.php mirip pola dengan index action lain (Employees/Suppliers): duplikasi pagination + sorting orchestration.
- app/Actions/AssetModels/IndexAssetModelsAction.php dan app/Actions/InventoryStocktakes/IndexInventoryStocktakesAction.php: duplikasi pola index action lintas modul inventory.

## Rencana Refactor Fokus Duplikasi (Batch C)

1. Ekstraksi query builder bersama untuk variances asset stocktake. (done)
	- Buat service khusus (contoh: AssetStocktakeVarianceQueryService) untuk membangun query dasar, filter request, dan sorting.
	- Reuse di IndexAssetStocktakeVarianceAction dan ExportAssetStocktakeVariancesAction agar blok 68 baris hilang dari kedua action.
2. Konsolidasi filter service asset family. (in-progress)
	- Tambah helper generik di BaseFilterService untuk relation sort berbasis mapping (join/leftJoin + select + orderBy).
	- Migrasikan AssetFilterService, AssetStocktakeFilterService, AssetMaintenanceFilterService, AssetMovementFilterService ke helper ini.
	- Progress: helper applyAssetAliasSearch sudah dipakai oleh AssetMaintenanceFilterService + AssetMovementFilterService.
	- Progress: helper applyMappedRelationSorting sudah dipakai oleh AssetFilterService + AssetStocktakeFilterService.
3. Standardisasi template index action.
	- Introduce trait/helper kecil untuk getPaginationParams + normalize sort direction + apply search/filters skeleton.
	- Terapkan ke IndexAssetsAction dan IndexAssetModelsAction terlebih dahulu, lalu modul inventory yang satu pola.
	- Progress: trait InteractsWithIndexRequest sudah dipakai oleh IndexAssetsAction, IndexAssetModelsAction, IndexInventoryStocktakesAction, IndexAssetStocktakesAction, IndexAssetMaintenancesAction, dan IndexProductsAction.
4. Frontend Batch C quick win (opsional tapi berdampak).
	- Satukan descriptor select async yang berulang pada AssetFilters.tsx dan ProductFilters.tsx lewat helper factory lokal.
	- Target: mengurangi duplikasi presentation-level tanpa ubah API backend.

## Rencana Penyempitan Konfigurasi Sonar (Bertahap)

1. Jangan perluas exclusion baru untuk menutup duplikasi.
	- Fokus utama tetap refactor kode agar gate membaik secara struktural.
2. Sempitkan sonar.cpd.exclusions setelah gelombang refactor pertama selesai.
	- Dari wildcard luas app/Exports/** dan app/Http/Resources/** menjadi daftar direktori yang memang template-heavy (misal report/export tertentu).
	- Lakukan perubahan bertahap per PR agar dampak ke gate bisa dipantau.
	- Progress: sonar.cpd.exclusions dipersempit ke report-focused path (Exports report + Http/Resources/Reports).
3. Pertahankan sonar.coverage.exclusions saat ini.
	- Cakupan ini sudah konsisten dengan sumber coverage PHPUnit dan tidak langsung mempengaruhi metrik duplikasi.

## Log Perubahan

- 2026-03-30: [C], fase 3 lanjutan: migrasi tambahan ke InteractsWithIndexRequest pada IndexAssetStocktakesAction, IndexAssetMaintenancesAction, dan IndexProductsAction; test: ./vendor/bin/sail artisan test tests/Unit/Actions/Products/IndexProductsActionTest.php tests/Unit/Actions/AssetMaintenances/IndexAssetMaintenancesActionTest.php tests/Feature/AssetStocktakes/AssetStocktakeControllerTest.php (PASS 7 test).
- 2026-03-30: [C], penyempitan config Sonar tahap 1: ubah sonar.cpd.exclusions dari wildcard luas ke report-focused path di .sonarcloud.properties; menunggu analisis CI Sonar untuk dampak metrik.
- 2026-03-30: [C], post-fase-3 cek Sonar MCP masih di snapshot gate ERROR (new_duplicated_lines_density 11.6); perlu trigger/menunggu analisis CI Sonar terbaru.
- 2026-03-30: [C], fase 3 awal: tambah trait InteractsWithIndexRequest (pagination + normalize sort direction) dan migrasi IndexAssetsAction, IndexAssetModelsAction, IndexInventoryStocktakesAction; test: ./vendor/bin/sail artisan test tests/Unit/Actions/Assets/IndexAssetsActionTest.php tests/Unit/Actions/AssetModels/IndexAssetModelsActionTest.php tests/Feature/AssetStocktakes/AssetStocktakeVarianceControllerTest.php (PASS 6 test).
- 2026-03-30: [C], fase 2 lanjutan: ekstraksi relation sorting helper BaseFilterService::applyMappedRelationSorting dan migrasi AssetFilterService + AssetStocktakeFilterService; test: ./vendor/bin/sail artisan test tests/Unit/Domain/Assets/AssetFilterServiceTest.php tests/Unit/Domain/AssetStocktakes/AssetStocktakeFilterServiceTest.php tests/Feature/AssetStocktakes/AssetStocktakeVarianceControllerTest.php (PASS 8 test).
- 2026-03-30: [C], post-refactor cek Sonar MCP masih menunjukkan snapshot gate ERROR (new_duplicated_lines_density 11.6); tunggu run CI Sonar berikutnya untuk melihat dampak refactor terbaru.
- 2026-03-30: [C], quick win fase 2: tambah helper BaseFilterService::applyAssetAliasSearch dan pakai di AssetMaintenanceFilterService + AssetMovementFilterService untuk kurangi duplikasi; test: ./vendor/bin/sail artisan test tests/Feature/AssetStocktakes/AssetStocktakeVarianceControllerTest.php tests/Unit/Domain/AssetStocktakes/AssetStocktakeFilterServiceTest.php (PASS 6 test).
- 2026-03-30: [C], eksekusi fase 1 refactor duplikasi variances asset stocktake dengan ekstraksi AssetStocktakeVarianceQueryService dan reuse pada index/export action; test: ./vendor/bin/sail artisan test tests/Feature/AssetStocktakes/AssetStocktakeVarianceControllerTest.php (PASS 3 test).
- 2026-03-30: [C], analisa duplikasi Sonar via MCP dan susun rencana refactor fokus penurunan duplication + strategi penyempitan config Sonar bertahap.
- 2026-03-30: [C], cek quality gate Sonar project gmedia_erp: ERROR karena new_duplicated_lines_density = 11.6 (threshold 3.0), hasil test: n/a, link PR: pending.
- 2026-03-30: [C], refresh snapshot Sonar untuk delta interim (semua metrik belum berubah dari baseline), hasil test: n/a, link PR: pending.
- 2026-03-30: [C], ambil baseline metrik via Sonar MCP (project: gmedia_erp), hasil test: n/a, link PR: pending.
- YYYY-MM-DD: [batch], ringkasan perubahan, hasil test, link PR.
