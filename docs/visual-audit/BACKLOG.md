# Visual Audit — Backlog

**Last updated:** 2026-08-10  
**Source:** multimodal-looker Wave 0–1  
**Policy:** T1–T5 landed on main. No **mass** Wave 2 (85 leaves). Prefer selective re-smoke of Wave 0–1 shells, then exception surfaces only.

## Open (prioritized)

| ID | Pri | Theme | Scope | Status | Notes |
|----|-----|-------|-------|--------|-------|
| — | — | — | — | **none** | All planned shared-shell themes T1–T5 closed. Next work is re-smoke / exceptions (below). |

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

## Residual / optional (not shared-shell themes)

| ID | Pri | Item | Notes |
|----|-----|------|-------|
| PO-05 | P2 | Grand Total column visibility | Product/column config, not shell |
| BS-01 | P2 | Compare “None” label opacity | Filter UX |
| SHELL-12 | P3 | Home stub vs financial dashboard | Product decision |

## Implementation rules

- One theme ≈ one `feat/*` or `fix/*` MR (AGENTS.md)
- Prefer shared chrome over per-module hacks
- Re-capture only **exception** surfaces later (modals, mobile, dark, My Approvals, asset profile) — not 85 leaves
- PNGs stay gitignored; cite paths in MR description
- Agents: **AGENTS.md Tool & Process Concurrency** (≤3 tools/turn; post-kill = 1 tool/turn)

## Next agent action

1. ~~Selective re-smoke~~ **Done 2026-08-10:** `VISUAL_AUDIT=1` + `playwright.visual-audit.config.ts` — **84/85 PASS**; **FAIL** `/asset-models` (bounced to `/login`). Note: harness walks full `url-list.json` (85), not Wave 0–1 only — treat as opportunistic full leaf pass; PNGs gitignored under `docs/visual-audit/waves/`  
2. Optional multimodal / human pass on key Wave 0–1 PNGs (employees, departments, PO, stock-movement report, dashboard, FD, accounts, balance-sheet)  
3. If clean: one **exception surface** MR (My Approvals **or** asset profile **or** modal) — not a second mass capture  
4. Optional: add route filter to visual-audit harness so “selective” ≠ all 85  
