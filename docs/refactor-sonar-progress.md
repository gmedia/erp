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

## Log Perubahan

- 2026-03-30: [C], cek quality gate Sonar project gmedia_erp: ERROR karena new_duplicated_lines_density = 11.6 (threshold 3.0), hasil test: n/a, link PR: pending.
- 2026-03-30: [C], refresh snapshot Sonar untuk delta interim (semua metrik belum berubah dari baseline), hasil test: n/a, link PR: pending.
- 2026-03-30: [C], ambil baseline metrik via Sonar MCP (project: gmedia_erp), hasil test: n/a, link PR: pending.
- YYYY-MM-DD: [batch], ringkasan perubahan, hasil test, link PR.
