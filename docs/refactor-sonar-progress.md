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
| C | in-progress | assets, products, asset-movements, asset-maintenances, asset-stocktakes | AssetFilterService, ProductFilterService, item controllers | snapshot 2026-03-31: quality gate ERROR (new_duplicated_lines_density 12.0 > 3.0) |
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

- duplicated_lines: +2 (6620 -> 6622, latest snapshot 2026-03-31)
- duplicated_blocks: +6 (373 -> 379, latest snapshot 2026-03-31)
- duplicated_lines_density: +0.0 (7.5 -> 7.5, latest snapshot 2026-03-31)
- ncloc: -326 (73194 -> 72868, latest snapshot 2026-03-31)
- coverage: +0.4 (86.5 -> 86.9, latest snapshot 2026-03-31)

## Snapshot Analisa Sonar (2026-03-31, latest MCP)

- Quality Gate: ERROR
- Gate blocker utama: new_duplicated_lines_density = 12.0 (threshold: 3.0)
- Catatan: snapshot terbaru menunjukkan coverage kembali normal di 86.9 (anomali 0.0 tidak muncul), namun gate masih tertahan oleh new_duplicated_lines_density.

### Prioritas Duplikasi Backend (Batch C)

- app/Domain/Assets/AssetFilterService.php <-> app/Domain/AssetStocktakes/AssetStocktakeFilterService.php: duplikasi mapping sorting/filter masih tinggi (blok 27-34 baris).
- app/Actions/AssetMaintenances/IndexAssetMaintenancesAction.php <-> app/Actions/AssetStocktakes/IndexAssetStocktakesAction.php <-> app/Actions/InventoryStocktakes/IndexInventoryStocktakesAction.php: duplikasi orchestration index action lintas modul (blok ~29-41 baris).
- app/Http/Requests/* (terutama pasangan Index*/Export*): pola rule request berulang lintas modul.
- app/Exports/ProductExport.php dan app/Exports/InventoryStocktakeExport.php: duplikasi struktur export map/header terhadap export non-report lain.

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
	- Progress: trait InteractsWithIndexRequest sudah dipakai oleh IndexAssetsAction, IndexAssetModelsAction, IndexInventoryStocktakesAction, IndexAssetStocktakesAction, IndexAssetMaintenancesAction, IndexProductsAction, IndexStockAdjustmentsAction, IndexStockTransfersAction, dan IndexEmployeesAction.
4. Frontend Batch C quick win (opsional tapi berdampak).
	- Satukan descriptor select async yang berulang pada AssetFilters.tsx dan ProductFilters.tsx lewat helper factory lokal.
	- Target: mengurangi duplikasi presentation-level tanpa ubah API backend.
5. Gelombang berikutnya (prioritas tinggi berdasarkan snapshot terbaru).
	- Refactor request berpasangan Index*/Export* ke reusable rule composer (khusus modul yang sama) agar blok validasi berulang turun tanpa ubah contract.
	- Standardisasi export non-report (ProductExport, InventoryStocktakeExport, dll.) lewat helper/concern shared untuk header-map + transform pipeline.
	- Lanjutkan template index action ke modul sibling (StockAdjustments/StockTransfers/Employees) untuk menurunkan clone block lintas actions.
	- Progress: request dedup sudah diterapkan untuk pasangan Products, InventoryStocktakes, dan AssetStocktakeVariances via abstract listing request.
	- Progress: export dedup sudah diperluas ke ProductExport, InventoryStocktakeExport, AssetStocktakeExport, GoodsReceiptExport, PurchaseOrderExport, SupplierReturnExport, PurchaseRequestExport, StockAdjustmentExport, StockTransferExport, SupplierExport, ApprovalFlowExport, PipelineExport, WarehouseExport, AssetLocationExport, AssetModelExport, CustomerExport, EmployeeExport, AssetExport, AssetMaintenanceExport, dan AssetMovementExport via concern InteractsWithExportFilters.

## Rencana Penyempitan Konfigurasi Sonar (Bertahap)

1. Jangan perluas exclusion baru untuk menutup duplikasi.
	- Fokus utama tetap refactor kode agar gate membaik secara struktural.
2. Sempitkan sonar.cpd.exclusions setelah gelombang refactor pertama selesai.
	- Dari wildcard luas app/Exports/** dan app/Http/Resources/** menjadi daftar direktori yang memang template-heavy (misal report/export tertentu).
	- Lakukan perubahan bertahap per PR agar dampak ke gate bisa dipantau.
	- Progress: sonar.cpd.exclusions dipersempit ke report-focused path (Exports report + Http/Resources/Reports).
	- Keputusan saat ini: jangan sempitkan lagi dulu sampai gelombang refactor request/export selesai, agar trend metrik bisa dibandingkan apple-to-apple.
3. Pertahankan sonar.coverage.exclusions saat ini.
	- Cakupan ini sudah konsisten dengan sumber coverage PHPUnit dan tidak langsung mempengaruhi metrik duplikasi.

## Log Perubahan

- 2026-03-31: [C], post-push 592aa718 cek Sonar MCP: snapshot masih sama (new_duplicated_lines_density 12.0; duplicated_lines 6622; duplicated_blocks 379; density 7.5; ncloc 72868; coverage 86.9).
- 2026-03-31: [C], wave semi-besar terkontrol (request dedup lanjutan): tambah BaseListingRequest dan migrasi sibling abstract listing request pada Assets, AssetMaintenances, Suppliers, PurchaseRequests, Products, InventoryStocktakes, AssetStocktakes Variances, serta ApprovalDelegations untuk konsolidasi authorize + sort rules; test: ./vendor/bin/sail artisan test tests/Unit/Requests/Assets/IndexAssetRequestTest.php tests/Unit/Requests/Assets/ExportAssetRequestTest.php tests/Unit/Requests/AssetMaintenances/IndexAssetMaintenanceRequestTest.php tests/Unit/Requests/AssetMaintenances/ExportAssetMaintenanceRequestTest.php tests/Unit/Requests/Suppliers/IndexSupplierRequestTest.php tests/Unit/Requests/Suppliers/ExportSupplierRequestTest.php tests/Unit/Requests/PurchaseRequests/IndexPurchaseRequestRequestTest.php tests/Unit/Requests/PurchaseRequests/ExportPurchaseRequestRequestTest.php tests/Unit/Requests/Products/IndexProductRequestTest.php tests/Unit/Requests/Products/ExportProductRequestTest.php tests/Unit/Requests/InventoryStocktakes/IndexInventoryStocktakeRequestTest.php tests/Unit/Requests/InventoryStocktakes/ExportInventoryStocktakeRequestTest.php tests/Unit/Requests/ApprovalDelegations/IndexApprovalDelegationRequestTest.php tests/Feature/AssetStocktakes/AssetStocktakeVarianceControllerTest.php (PASS 33 test).
- 2026-03-31: [C], post-rebase/push 4fe7f6de cek Sonar MCP: snapshot belum berubah dari run sebelumnya (new_duplicated_lines_density 12.0; duplicated_lines 6710; duplicated_blocks 390; density 7.6; ncloc 72984; coverage 0.0/anomali pipeline coverage).
- 2026-03-31: [C], wave semi-besar terkontrol (export dedup lanjutan 4): migrasi CustomerExport, EmployeeExport, AssetExport, AssetMaintenanceExport, dan AssetMovementExport ke concern InteractsWithExportFilters (shared search/exact/sort/styles + normalisasi sort direction); test: ./vendor/bin/sail artisan test tests/Feature/Customers/CustomerExportTest.php tests/Feature/Employees/EmployeeExportTest.php tests/Feature/AssetMaintenances/AssetMaintenanceExportTest.php tests/Feature/AssetMovements/AssetMovementExportTest.php tests/Unit/Actions/Assets/ExportAssetsActionTest.php tests/Unit/Actions/AssetMaintenances/ExportAssetMaintenancesActionTest.php tests/Unit/Actions/AssetMovements/ExportAssetMovementsActionTest.php (PASS 30 test).
- 2026-03-31: [C], post-push c563e888 cek Sonar MCP: gate masih ERROR namun membaik (new_duplicated_lines_density 12.0, dari 12.1); metrik inti terbaru: duplicated_lines 6710, duplicated_blocks 390, density 7.6, ncloc 72984, coverage 0.0 (indikasi anomali pipeline coverage).
- 2026-03-31: [C], wave export dedup lanjutan 3: perluas InteractsWithExportFilters (normalizeSortDirection + applyPresentFilters) lalu migrasi ApprovalFlowExport, PipelineExport, WarehouseExport, AssetLocationExport, dan AssetModelExport ke concern shared; test: ./vendor/bin/sail artisan test tests/Feature/ApprovalFlows/ApprovalFlowExportTest.php tests/Feature/Pipelines/PipelineExportTest.php tests/Feature/Warehouses/WarehouseExportTest.php tests/Feature/AssetLocations/AssetLocationExportTest.php tests/Feature/AssetModels/AssetModelExportTest.php (PASS 12 test).
- 2026-03-31: [C], post-push d67b614b cek Sonar MCP: gate masih ERROR tetapi membaik (new_duplicated_lines_density 12.1, dari 12.4); metrik inti terbaru: duplicated_lines 6864, duplicated_blocks 431, density 7.7, ncloc 73082, coverage 87.0.
- 2026-03-31: [C], wave export dedup lanjutan 2: migrasi PurchaseRequestExport, StockAdjustmentExport, StockTransferExport, dan SupplierExport ke concern InteractsWithExportFilters (shared search/exact/date/sort/styles); test: ./vendor/bin/sail artisan test tests/Feature/PurchaseRequests/PurchaseRequestExportTest.php tests/Feature/StockAdjustments/StockAdjustmentExportTest.php tests/Feature/StockTransfers/StockTransferExportTest.php tests/Feature/Suppliers/SupplierExportTest.php (PASS 13 test).
- 2026-03-31: [C], post-push d77f5fcb cek Sonar MCP: gate masih ERROR (new_duplicated_lines_density 12.4), namun duplicated_lines turun (7059 -> 6981); metrik terbaru: duplicated_blocks 499, density 7.9, ncloc 73178, coverage 86.9.
- 2026-03-31: [C], wave export dedup lanjutan: migrasi AssetStocktakeExport, GoodsReceiptExport, PurchaseOrderExport, dan SupplierReturnExport ke concern InteractsWithExportFilters (shared search/exact/date/sort/styles); test: ./vendor/bin/sail artisan test tests/Feature/AssetStocktakes/AssetStocktakeControllerTest.php tests/Feature/GoodsReceipts/GoodsReceiptExportTest.php tests/Feature/PurchaseOrders/PurchaseOrderExportTest.php tests/Feature/SupplierReturns/SupplierReturnExportTest.php (PASS 7 test).
- 2026-03-31: [C], post-push d1d3c8cf cek ulang Sonar MCP: quality gate tetap ERROR (new_duplicated_lines_density 12.4), metrik inti belum berubah (duplicated_lines 7059, duplicated_blocks 495, density 7.9, ncloc 73197, coverage 87.0).
- 2026-03-31: [C], lanjutan standardisasi index action: migrasi IndexStockAdjustmentsAction, IndexStockTransfersAction, dan IndexEmployeesAction ke InteractsWithIndexRequest; test: ./vendor/bin/sail artisan test tests/Unit/Actions/StockAdjustments/IndexStockAdjustmentsActionTest.php tests/Unit/Actions/StockTransfers/IndexStockTransfersActionTest.php tests/Unit/Actions/Employees/IndexEmployeesActionTest.php tests/Feature/InventoryStocktakes/InventoryStocktakeControllerTest.php (PASS 18 test).
- 2026-03-31: [C], tarik ulang metrik Sonar MCP dan update Delta final: duplicated_lines 7059, duplicated_blocks 495, duplicated_lines_density 7.9, ncloc 73197, coverage 87.0; quality gate tetap ERROR (new_duplicated_lines_density 12.4).
- 2026-03-30: [C], eksekusi wave export dedup tahap awal: tambah concern InteractsWithExportFilters lalu migrasi ProductExport + InventoryStocktakeExport; test: ./vendor/bin/sail artisan test tests/Unit/Actions/Products/ExportProductsActionTest.php tests/Unit/Actions/InventoryStocktakes/ExportInventoryStocktakesActionTest.php tests/Feature/InventoryStocktakes/InventoryStocktakeControllerTest.php (PASS 7 test).
- 2026-03-30: [C], eksekusi wave request dedup: tambah abstract listing request untuk Products, InventoryStocktakes, dan AssetStocktakeVariances lalu migrasi Index*/Export* request; test: ./vendor/bin/sail artisan test tests/Unit/Requests/Products/IndexProductRequestTest.php tests/Unit/Requests/Products/ExportProductRequestTest.php tests/Feature/AssetStocktakes/AssetStocktakeVarianceControllerTest.php tests/Feature/InventoryStocktakes/InventoryStocktakeControllerTest.php (PASS 14 test).
- 2026-03-30: [C], refresh analisa Sonar MCP terbaru: quality gate tetap ERROR dan new_duplicated_lines_density naik ke 12.5; update delta metrics + prioritas refactor request/export/index-action.
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
