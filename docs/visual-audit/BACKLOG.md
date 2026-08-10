# Visual Audit — Backlog

**Last updated:** 2026-08-10  
**Source:** multimodal-looker Wave 0–1  
**Policy:** No Wave 2 mass capture until T1–T3 land or shared-shell re-reviewed

## Open (prioritized)

| ID | Pri | Theme | Scope | Status | Notes |
|----|-----|-------|-------|--------|-------|
| T1 | P0–P1 | DataTable shell v2 | EntityCrudPage / shared table | open (PR #90) | Single toolbar row; sticky Actions; semantic Status; scroll discipline; bulk bar optional. Fixes SHELL-02,03,06,07,10,11; EMP-*; PO-* |
| T2 | P1 | Sidebar IA & active | AppLayout / nav | open (PR #91) | Truncation strategy; density residual after HF-3. SHELL-01,08,09 |
| T3 | P1 | Page header contract | Layout primitive | open (PR #92) | Shared `PageHeader`; Dashboard/Report/Financial shells; accounts ACC-01; stock-movements RSM-02; FD-06 title. SHELL-04 |
| T4 | P0–P1 | Dashboard & KPI semantics | Home + financial dash + amounts | open | Home content strategy; **danger for negatives**; hide comparison until Compare set; FY visible. FD-01..03 residual; DASH-*; BS-02 |
| T5 | P1–P2 | Sparse & report density | ReportDataTable + list | open | Summary strip / empty panels; report density. SHELL-05; RSM-01 |

## Hotfixes (can ship before full T1)

| ID | Pri | Item | Status |
|----|-----|------|--------|
| HF-1 | P0 | Financial dashboard: negative Cash Balance must not use success green | done (fix/hf1) |
| HF-2 | P0 | Employees (and wide tables): sticky Actions + horizontal scroll | done (fix/hf2) |
| HF-3 | P1 | Accounts: fix sidebar active state for Chart of Accounts | done (fix/hf3) |

## Done

| ID | Notes |
|----|-------|
| VA-W0-SETUP | harness, url-list, visual-audit config, smoke |
| VA-W1-CAPTURE | 7 routes PNG |
| VA-W1-VISION | multimodal-looker full audit; stop-rule YES |

## Implementation rules

- One theme ≈ one `feat/*` or `fix/*` MR (AGENTS.md)
- Prefer shared chrome over per-module hacks
- Re-capture only **exception** surfaces later (modals, mobile, dark, My Approvals, asset profile) — not 85 leaves
- PNGs stay gitignored; cite paths in MR description

## Next agent action

1. Human: merge **PR #90** (T1), **#91** (T2), and T3 PR when green — order flexible  
2. After merges: pull main; mark T1–T3 done in this table  
3. Do **not** mass Wave 2 capture; next theme **T4** or residual polish
