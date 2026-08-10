# Visual Audit — Backlog

**Last updated:** 2026-08-10  
**Source:** multimodal-looker Wave 0–1  
**Policy:** No Wave 2 mass capture until T1–T5 land or shared-shell re-reviewed

## Open (prioritized)

| ID | Pri | Theme | Scope | Status | Notes |
|----|-----|-------|-------|--------|-------|
| T1 | P0–P1 | DataTable shell v2 | EntityCrudPage / shared table | **done (PR #90 merged)** | Single toolbar row; sticky Actions (HF-2); semantic Status; bulk bar; pagination chrome. SHELL-02,03,06,10,11; EMP-*; PO-* |
| T2 | P1 | Sidebar IA & active | AppLayout / nav | **done (PR #91 merged)** | Density + truncation tooltips; active route = HF-3. SHELL-01,09 residual closed with T2 |
| T3 | P1 | Page header contract | Layout primitive | **done (PR #92 merged)** | Shared `PageHeader`; Dashboard/Report/Financial shells; accounts ACC-01; stock-movements RSM-02; FD-06 title. SHELL-04 |
| T4 | P0–P1 | Dashboard & KPI semantics | Home + financial dash + amounts | **done (PR #93 merged)** | FD-02 FY URL sync; FD-03 hide YoY when Compare=None; BS-02 signed amount rose chrome; DASH-01 home via DashboardPageShell + KpiCard |
| T5 | P1–P2 | Sparse & report density | ReportDataTable + list | **in progress (this PR)** | Content-height shells (drop h-full flex-1 void); denser DataTable + pagination. SHELL-05; RSM-01 |

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
| T1 | PR #90 merged into main |
| T2 | PR #91 merged into main |
| T3 | PR #92 merged into main |
| T4 | PR #93 merged into main |

## Implementation rules

- One theme ≈ one `feat/*` or `fix/*` MR (AGENTS.md)
- Prefer shared chrome over per-module hacks
- Re-capture only **exception** surfaces later (modals, mobile, dark, My Approvals, asset profile) — not 85 leaves
- PNGs stay gitignored; cite paths in MR description

## Next agent action

1. Finish **T5** PR: types → commit → push → `gh pr create`  
2. After T5 merge (or shared-shell re-review): consider unfreezing selective Wave 2 capture  
3. Do **not** mass Wave 2 capture yet  
4. Agents: respect **AGENTS.md Tool & Process Concurrency** (max 3 tools/turn; post-kill = 1 tool/turn)
