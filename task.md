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

## Recommended next (pick one)

### A — Selective visual re-smoke (recommended default)
- **Only** `playwright.visual-audit.config.ts`
- 1 worker; base `http://127.0.0.1:82`; admin login
- Re-capture Wave 0–1 shell routes; PNGs gitignored
- Optional multimodal-looker / human: confirm SHELL-05 void reduced; no T1–T4 regressions

### B — Exception surface (one MR)
- My Approvals **or** asset profile **or** dense modal — not full leaf inventory

### C — Residual FINDINGS (optional product)
- PO-05 Grand Total column; BS-01 Compare label; SHELL-12 home vs FD product call

## Continuation Prompt

Main at `03c22267` (T5 #94 merged). T1–T5 closed. Run selective Wave 0–1 re-smoke with visual-audit config only, then optionally one exception-surface MR. No mass Wave 2. Keep `e2e/` untracked.
