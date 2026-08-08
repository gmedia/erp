# Visual Audit — Findings

**Wave:** 0 + 1 (capture + multimodal vision)  
**Last updated:** 2026-08-08  
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
| SHELL-12 | P3 | DASH vs FD | Home stub vs rich financial dashboard |

**Material shared-shell count:** 11 (≥3 threshold).

## High-signal per-route (non-exhaustive)

| ID | Sev | Route | Summary |
|----|-----|-------|---------|
| DASH-01 | P1 | `/dashboard` | 4 count cards + ~60% empty; unfinished home |
| EMP-02 | P1 | `/employees` | Toolbar 2-row mess |
| EMP-03 | P1 | `/employees` | All status “Regular” solid black |
| PO-01 | P1 | `/purchase-orders` | 8 statuses look identical; critical states don’t pop |
| PO-04 | P2 | `/purchase-orders` | No Actions column visible |
| PO-05 | P2 | `/purchase-orders` | Grand Total not in visible columns |
| ACC-01 | P1 | `/accounts` | Double title (breadcrumb + H1 same) |
| ACC-02 | P1 | `/accounts` | Wrong sidebar active state |
| RSM-01 | P1 | stock-movement | 2 rows + ~70% white void |
| RSM-02 | P2 | stock-movement | No H1 (unlike Balance Sheet) |
| BS-01 | P2 | balance-sheet | Bare selects; “None” opaque without Compare label |
| BS-02 | P2 | balance-sheet | Negative amounts same black as positive |
| FD-02 | P1 | financial-dashboard | FY shows “Select…” while KPIs full |
| FD-03 | P1 | financial-dashboard | “0.00% vs comparison” with Compare = None |
| FD-06 | P2 | financial-dashboard | Breadcrumb “Financial Dashboard” vs H1 “Financial Overview” |

Full multimodal narrative: session `ses_02021a5c2ffeaD0HIIg6ymhnEW`.

## Themes → BACKLOG

T1 DataTable shell v2 · T2 Sidebar IA/active · T3 Page header contract · T4 KPI/dashboard semantics · T5 Sparse/report density
