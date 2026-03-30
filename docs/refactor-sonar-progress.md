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
| C | next | assets, products, asset-movements, asset-maintenances, asset-stocktakes | AssetFilterService, ProductFilterService, item controllers | pending |
| D | next | financial-reporting | FinancialReportService + query/mapping laporan keuangan | pending |
| E | next | account-mappings, journal-entries, goods-receipts, purchase-requests | pasangan Store*Request/Update*Request | pending |

## Baseline Metrics

Isi saat mulai batch baru.

- duplicated_lines:
- duplicated_blocks:
- duplicated_lines_density:
- ncloc:
- coverage:

## Delta Metrics (setelah batch)

Isi setelah batch selesai dan sebelum merge.

- duplicated_lines:
- duplicated_blocks:
- duplicated_lines_density:
- ncloc:
- coverage:

## Log Perubahan

- YYYY-MM-DD: [batch], ringkasan perubahan, hasil test, link PR.
