# Visual Audit — Backlog

**Last updated:** 2026-08-19 (#108 merged)  
**Source:** multimodal-looker Wave 0–1  
**Policy:** T1–T5 + EX themes on main. No **mass** Wave 2 (85 leaves). Prefer selective re-smoke via `VISUAL_AUDIT_PRESET` / `VISUAL_AUDIT_ROUTES`.

## Themes

| ID | Theme | Status |
|----|-------|--------|
| T1–T5 | Shared shell | **merged** (#90–#94) |
| Closeout | BACKLOG / task handoff | **merged** (#95) |
| Harness | Named presets | **merged** (#97) |
| EX-1 | My Approvals inbox | **merged** (#96) |
| EX-auth | Capture auth settle | **merged** (#98) |
| EX-asset-profile | Asset profile chrome | **merged** (#99) |
| EX-modal | View/form modal chrome | **merged** (#100) |

## Exception rows

| ID | Pri | Theme | Scope | Status | Notes |
|----|-----|-------|-------|--------|-------|
| EX-1 | P1 | My Approvals chrome | `/my-approvals` | **merged #96** | Densify inbox |
| EX-auth | P1 | Capture auth settle | visual harness | **merged #98** | `requireDashboard` + re-login |
| EX-asset-profile | P1 | Asset profile chrome | `/assets/:ulid` | **merged #99** | PageHeader + compact tabs/cards |
| EX-modal | P1 | View/form modal chrome | ViewModalShell + EntityForm | **merged #100** | Tighter padding, title, ViewField |

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
| T1 | PR #90 — DataTable shell v2 |
| T2 | PR #91 — Sidebar density + active |
| T3 | PR #92 — Page header contract |
| T4 | PR #93 — Dashboard & KPI semantics |
| T5 | PR #94 — Sparse & report density (SHELL-05; RSM-01) |
| BS-02 | Signed amounts rose for negatives (`FinancialReportSection`) |
| FD-03 | KPI vs-comparison hidden when Compare is None |
| FD-06 | Breadcrumb + H1 both “Financial Overview” |
| RSM-02 | Stock Movements page has title + description |

## Residual / optional (not shared-shell themes)

| ID | Pri | Item | Notes |
|----|-----|------|-------|
| PO-05 | P2 | Grand Total column visibility | **merged #107** — sticky when `grand_total` is immediately left of Actions (PO, credit notes; not invoice/bill Amount Due) |
| BS-01 | P2 | Compare “None” label opacity | **merged #102** — labeled Fiscal Year / Compare; None muted |
| SHELL-12 | P3 | Home stub vs financial dashboard | **merged #108** — shortcuts + master-data mix; keep separate FD route/permission |
| FD-02 | P1 | FY “Select…” while KPIs full | **merged #104** |

## Implementation rules

- One theme ≈ one `feat/*` or `fix/*` MR (AGENTS.md)
- Prefer shared chrome over per-module hacks
- Re-capture only **exception** surfaces later — not 85 leaves
- PNGs stay gitignored; cite paths in MR description
- Agents: **AGENTS.md Tool & Process Concurrency** (≤3 tools/turn; post-kill = 1 tool/turn)

## Harness route selection

| Priority | Env | Effect |
|----------|-----|--------|
| 1 | `VISUAL_AUDIT_ROUTES=/a,/b` | Comma list only |
| 2 | `VISUAL_AUDIT_PRESET=shells` | Named set in `presets.json` (`shells`, `wave-0`, `exceptions`, `dashboards`, `smoke`) |
| 3 | (none) | Full `url-list.json` (85) — avoid on low-RAM hosts |

```bash
VISUAL_AUDIT=1 VISUAL_AUDIT_PRESET=shells PLAYWRIGHT_BASE_URL=http://127.0.0.1:82 \
  npx playwright test -c playwright.visual-audit.config.ts
```

## Next agent action

1. ~~#96–#102~~ **merged** on main (EX themes + BS-01). Docs closeout #101 merged; this PR #103 FINDINGS landed.
2. Keep `e2e/` untracked.
3. Optional: `VISUAL_AUDIT_PRESET=exceptions` re-smoke — do not commit PNGs.
4. PO-05 / SHELL-12 / FD-02 only with product call — not mass Wave 2.
