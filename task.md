# task.md — Active Session Handoff

**Last updated:** 2026-08-11  
**Current milestone:** Visual audit — T1–T5 + closeout merged; harness presets open; My Approvals open  
**Branch tip (this work):** `chore/va-harness-allowlist` @ `485a2203`  
**main tip (when last pulled):** `e1e1bd20` (closeout #95)  
**Open PRs:** [#96](https://github.com/gmedia/erp/pull/96) My Approvals · [#97](https://github.com/gmedia/erp/pull/97) harness presets  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md`  
3. `docs/visual-audit/FINDINGS.md`  
4. `docs/visual-audit-plan.md`

## Done

- Wave 0–1 harness + plan + FINDINGS/BACKLOG (PR #86)  
- **HF-1–3** on main  
- **T1–T5** PRs #90–#94 merged  
- **Closeout** #95 merged  
- Full re-smoke **84/85** (`/asset-models` → login)  
- **My Approvals chrome** — PR **#96** (branch `feat/va-my-approvals-chrome`)  
- **Harness allowlist** — PR **#97** (`VISUAL_AUDIT_PRESET` + `docs/visual-audit/presets.json`)

## Themes status

| ID | Theme | Status |
|----|-------|--------|
| T1–T5 | Shared shell | **merged** |
| EX-1 | My Approvals inbox | **PR #96 open** |
| Harness | Named presets | **PR #97 open** |

## Do not

- Mass-capture Wave 2 / remaining ~78 routes  
- Use default `playwright.config.ts` for visual (`migrate:fresh`)  
- Commit untracked `e2e/`  
- Wait on CI  
- Parallel tool fan-out (≤3 tools/turn; post-kill = 1)

## Selective capture (PR #97)

```bash
VISUAL_AUDIT=1 VISUAL_AUDIT_PRESET=shells PLAYWRIGHT_BASE_URL=http://127.0.0.1:82 \
  npx playwright test -c playwright.visual-audit.config.ts
```

Presets: `shells` | `wave-0` | `exceptions` | `dashboards` | `smoke`  
Or: `VISUAL_AUDIT_ROUTES=/dashboard,/my-approvals`  
Without preset/routes → full 85 (avoid on low RAM).

## Recommended next

1. Merge **#96** / **#97** when ready (no CI wait)  
2. Optional: `PRESET=smoke` or `shells` local re-smoke  
3. Next exception: **asset profile** or dense modal (new branch from main)  
4. Optional: fix `/asset-models` capture auth bounce  

## Continuation Prompt

```
Read task.md. Open PRs #96 (My Approvals) and #97 (harness presets).
Do not poll CI. Next shippable: merge those or start exception surface
(asset profile / modal) from main. Prefer VISUAL_AUDIT_PRESET=shells for re-smoke.
Keep e2e/ untracked. AGENTS concurrency ≤3 tools/turn.
```
