# Visual Audit — Findings

**Wave:** 0 + 1 (capture + multimodal vision)  
**Last updated:** 2026-08-18  
**Reviewer:** multimodal-looker (`ses_02021a5c2ffeaD0HIIg6ymhnEW`)  
**Viewport:** 1440×900  
**Stop-rule (≥3 shared-shell):** **YES — freeze Wave 2 mass capture**

## Wave capture status

| Wave | Routes | Status |
|------|--------|--------|
| 0 | `/dashboard` | Capture OK |
| 1 | 7 shells (CRUD/report/financial) | Capture OK + vision |

## P0 (must fix first)

| ID | Route | Summary | Screenshot |
|----|-------|---------|------------|
| EMP-01 | `/employees` | Table clips right: salary truncated; **Actions column not visible**; no sticky actions | `wave-1/employees.png` |
| FD-01 | `/financial-dashboard` | **Cash Balance negative** with **green/teal** (positive) semantic | `wave-1/financial-dashboard.png` |
| SHELL-07 | EMP, PO | Horizontal overflow / missing sticky Actions (systemic) | wave-1 |

## Shared-shell (P1+) — stop mass capture

| ID | Sev | Screens | Issue |
|----|-----|---------|-------|
| SHELL-01 | P1 | All 8 | Sidebar nested labels truncated (ellipsis) |
| SHELL-02 | P1 | DEPT EMP PO RSM | Toolbar 1 vs 2 rows; Columns orphaned |
| SHELL-03 | P1 | DEPT EMP PO RSM | Search left / actions far right; dead gap |
| SHELL-04 | P1 | DASH vs ACC/BS/FD | Page chrome split: breadcrumb-only vs H1+desc |
| SHELL-05 | P1 | Sparse tables | Huge empty canvas under few rows |
| SHELL-06 | P1 | EMP PO | Status badges non-semantic (mono black/outline) |
| SHELL-07 | P0–P1 | EMP PO | Table overflow / no sticky Actions |
| SHELL-08 | P1 | ACC | Sidebar **active item wrong** (Department while on CoA) |
| SHELL-09 | P2 | Deep nav | Long nested lists; active child hard to see |
| SHELL-10 | P2 | CRUD | Checkbox without bulk action UX |
| SHELL-11 | P2 | DataTable | Pagination weak in empty footer zone |
| SHELL-12 | P3 | DASH vs FD | **in this MR:** shortcuts + master-data mix; do not merge with financial dashboard |

**Material shared-shell count:** 11 (≥3 threshold).

## High-signal per-route (non-exhaustive)

| ID | Sev | Route | Summary |
|----|-----|-------|---------|
| DASH-01 | P1 | `/dashboard` | 4 count cards + ~60% empty; unfinished home |
| EMP-02 | P1 | `/employees` | Toolbar 2-row mess |
| EMP-03 | P1 | `/employees` | All status “Regular” solid black |
| PO-01 | P1 | `/purchase-orders` | 8 statuses look identical; critical states don’t pop |
| PO-04 | P2 | `/purchase-orders` | No Actions column visible |
| PO-05 | P2 | `/purchase-orders` | **in this MR** — sticky Grand Total immediately left of Actions |
| ACC-01 | P1 | `/accounts` | Double title (breadcrumb + H1 same) |
| ACC-02 | P1 | `/accounts` | Wrong sidebar active state |
| RSM-01 | P1 | stock-movement | 2 rows + ~70% white void |
| RSM-02 | P2 | stock-movement | **landed** — `title` + description on ReportDataTablePage |
| BS-01 | P2 | balance-sheet | Bare selects; “None” opaque without Compare label |
| BS-02 | P2 | balance-sheet | **landed** — `getSignedAmountTextClass` rose for negatives |
| FD-02 | P1 | financial-dashboard | **landed** — FY select binds to preferred year; keepPreviousData on refetch |
| FD-03 | P1 | financial-dashboard | **landed** — KPI “vs comparison” gated on `showComparison` |
| FD-06 | P2 | financial-dashboard | **landed** — breadcrumb + H1 both “Financial Overview” |

Full multimodal narrative: session `ses_02021a5c2ffeaD0HIIg6ymhnEW`.

## Themes → BACKLOG

T1 DataTable shell v2 · T2 Sidebar IA/active · T3 Page header contract · T4 KPI/dashboard semantics · T5 Sparse/report density

---

## Re-smoke 2026-11-17 (issue #116) — code-backed checklist

Harness: `VISUAL_AUDIT=1 VISUAL_AUDIT_PRESET=smoke` (3 passed) + `exceptions` (5 passed), 1 worker, dijalankan **di dalam container** dengan `PLAYWRIGHT_BASE_URL=http://127.0.0.1:80` (bukan `:82` published host — dari container `:82` mengarah ke dirinya sendiri dan ERR_CONNECTION_REFUSED). PNG otomatis ter-ignore via `.gitignore`.

Checklist `docs/ui-design-rules.md` Bagian 3 dipakai dalam mode **code-backed review** (PNG tidak dapat dibaca oleh agent tanpa vision di sesi ini, sehingga verifikasi dilakukan terhadap komponen/shared shell, bukan piksel):

| Aturan | Bukti di kode | Verdict |
|--------|---------------|---------|
| D. Actions sticky kanan | `DataTableCore.tsx` `STICKY_ACTIONS_CELL/HEAD` = `sticky right-0` | PASS |
| E. Angka negatif bukan hijau | `FinancialReportSection.getSignedAmountTextClass` → rose untuk `value < 0`; `SummaryCards.signedBalanceChrome` → `border-l-rose-500`, `text-rose-600` | PASS (HF-1/FD-01 fixed) |
| B. Page shell konsisten | route CRUD/report memakai factory / `ReportDataTablePage` | PASS |
| A–C, F–G (chrome, toolbar, form/modal, teknis) | Tidak diverifikasi visual pada sesi ini | UNCLEAR |

Catatan: untuk verdict visual penuh (SHELL-01/02/03/06/08, double title), diperlukan agent dengan kemampuan vision atau OCR terstruktur. Tidak ada finding baru yang ditambahkan; ini hanya konfirmasi bahwa perbaikan lama masih hadir di kode setelah re-smoke.
