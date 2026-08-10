# task.md — Active Session Handoff

**Last updated:** 2026-08-10  
**Current milestone:** Visual audit — **T1–T5 merged**; next = selective re-smoke / exception surfaces  
**Branch:** `main` @ `03c22267` (T5 #94 squash)  
**T1–T5:** PRs #90–#94 **merged**  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md`  
3. `docs/visual-audit/FINDINGS.md`  
4. `docs/visual-audit-plan.md`

## Done

- Wave 0–1 harness + plan + FINDINGS/BACKLOG (PR #86)  
- **HF-1–3** on main  
- **T1** DataTable shell v2 — **#90**  
- **T2** Sidebar density — **#91**  
- **T3** Page header — **#92**  
- **T4** Dashboard & KPI — **#93** (FD-02/03, BS-02, DASH-01)  
- **T5** Sparse & report density — **#94** (SHELL-05; denser list/report shells)

## Themes status

| ID | Theme | Status |
|----|-------|--------|
| T1 | DataTable shell v2 | **merged** (#90) |
| T2 | Sidebar IA residual | **merged** (#91) |
| T3 | Page header contract | **merged** (#92) |
| T4 | Dashboard & KPI | **merged** (#93) |
| T5 | Sparse & report density | **merged** (#94) |

## Do not

- Mass-capture Wave 2 / remaining ~78 routes (exception surfaces only after re-smoke)  
- Use default `playwright.config.ts` for visual (migrate:fresh)  
- Redesign all 85 modules in one MR  
- Commit local untracked `e2e/` junk  
- Wait on CI (AGENTS: never wait for CI)  
- Parallel tool fan-out (AGENTS: ≤3 tools/turn; post-kill = 1)

## Re-smoke (2026-08-10)

- Command: `VISUAL_AUDIT=1 PLAYWRIGHT_BASE_URL=http://127.0.0.1:82 npx playwright test -c playwright.visual-audit.config.ts`
- Result: **84 pass / 1 fail** (`/asset-models` → login bounce)
- PNGs: `docs/visual-audit/waves/**` (gitignored). Harness uses full MenuSeeder url-list (85), not Wave 0–1 subset

## Recommended next (pick one)

### A — Human / multimodal spot-check (recommended)
- Review Wave 0–1 shell PNGs post-T5 (SHELL-05 density, no T1–T4 regressions)
- Optionally fix `/asset-models` auth/permission for visual capture

### B — Exception surface (one MR)
- My Approvals **or** asset profile **or** dense modal — not full leaf inventory

### C — Residual FINDINGS (optional product)
- PO-05 Grand Total column; BS-01 Compare label; SHELL-12 home vs FD product call

### D — Harness improvement (optional chore)
- Env allowlist so re-smoke can be truly selective (7–8 routes)

## Continuation Prompt

Closeout PR #95 open. Main tip pre-closeout: `03c22267` (T5 #94). Re-smoke 84/85 done (asset-models fail). Next: spot-check PNGs or one exception-surface MR. Keep `e2e/` untracked.
