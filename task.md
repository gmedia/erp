# task.md — Active Session Handoff

**Last updated:** 2026-08-08  
**Current milestone:** Visual audit — **Wave 0–1 complete** (capture + vision); **Wave 2 mass capture FROZEN**  
**Branch:** `chore/visual-audit-wave-0-1`  
**Open PR:** https://github.com/gmedia/erp/pull/86  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md` (T1–T5 + hotfixes)  
3. `docs/visual-audit/FINDINGS.md`  
4. `docs/visual-audit-plan.md` (program; stop-rule applied)

## Done

- Wave 0 setup + smoke `/dashboard`
- Wave 1 capture 7/7 PASS
- Multimodal vision on 8 PNGs → FINDINGS + BACKLOG
- **Stop-rule YES** (≥3 shared-shell; ~11 material)

## P0 callouts

1. **FD-01** Negative cash balance styled green  
2. **EMP-01 / SHELL-07** Wide tables clip Actions  
3. **ACC-02 / SHELL-08** Wrong sidebar active on Chart of Accounts  

## Themes (do not implement until user picks)

T1 DataTable shell · T2 Sidebar · T3 Page header · T4 KPI semantics · T5 Sparse density  

Hotfixes HF-1..3 in BACKLOG for smaller MRs.

## Do not

- Mass-capture Wave 2 / remaining 78 routes  
- Use default `playwright.config.ts` for visual (migrate:fresh)  
- Redesign all 85 modules in one MR  

## Capture (exceptions only)

```bash
VISUAL_AUDIT=1 VISUAL_AUDIT_WAVE=wave-X VISUAL_AUDIT_ROUTES='...' \
  PLAYWRIGHT_BASE_URL=http://127.0.0.1:82 \
  npx playwright test -c playwright.visual-audit.config.ts --workers=1
```

## Recommended next

1. User: **commit** chore docs/harness **or** implement **HF-1** (cash color) / **HF-2** (sticky actions) / **T1** plan  
2. After shared chrome ships: optional re-smoke 3–5 routes only  

## Continuation Prompt

```
Read task.md + docs/visual-audit/BACKLOG.md + FINDINGS.md.
Wave 0–1 capture+vision done; Wave 2 mass capture FROZEN (stop-rule).
P0: FD-01 negative cash green; EMP sticky Actions; ACC sidebar active.
Next only if user asks: commit chore OR implement HF/T theme (one MR).
Vision session: ses_02021a5c2ffeaD0HIIg6ymhnEW. Base :82 workers=1.
```
