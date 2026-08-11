# Visual Audit — Backlog

**Last updated:** 2026-08-11  
**Source:** multimodal-looker Wave 0–1  
**Policy:** T1–T5 + harness presets on main. No **mass** Wave 2 (85 leaves). Prefer selective re-smoke via `VISUAL_AUDIT_PRESET` / `VISUAL_AUDIT_ROUTES`, then exception surfaces only.

## Themes

| ID | Theme | Status |
|----|-------|--------|
| T1–T5 | Shared shell | **merged** (#90–#94) |
| Closeout | BACKLOG / task handoff | **merged** (#95) |
| Harness | Named presets | **merged** (#97) |
| EX-1 | My Approvals inbox | **merged** (#96) |
| EX-auth | Capture auth settle | **merged** (#98) |
| EX-asset-profile | Asset profile chrome | **PR #99** (rebasing) |

## Exception rows

| ID | Pri | Theme | Scope | Status | Notes |
|----|-----|-------|-------|--------|-------|
| EX-1 | P1 | My Approvals chrome | `/my-approvals` | **merged #96** | Densify inbox |
| EX-auth | P1 | Capture auth settle | visual harness | **merged #98** | `requireDashboard` + re-login |
| EX-asset-profile | P1 | Asset profile chrome | `/assets/:ulid` | **PR #99** | PageHeader + compact tabs/cards |

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

1. ~~Selective re-smoke~~ **Done 2026-08-10:** full catalog **84/85 PASS**; **FAIL** `/asset-models` → `/login` (fixed **#98 merged**).
2. ~~Harness allowlist~~ **Done 2026-08-11:** `VISUAL_AUDIT_PRESET` + `docs/visual-audit/presets.json` (**#97 merged**).
3. ~~#96 / #98~~ **merged** on main.
4. Finish **#99** rebase → MERGEABLE → merge when ready (no CI wait).
5. Optional: selective capture of asset profile URL only; multimodal on Wave 0–1 PNGs.
6. Optional residual FINDINGS (PO-05 / BS-01 / SHELL-12) only with product call — not mass Wave 2.
