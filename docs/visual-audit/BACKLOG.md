# Visual Audit — Backlog

**Last updated:** 2026-08-11  
**Source:** multimodal-looker Wave 0–1  
**Policy:** T1–T5 landed on main. No **mass** Wave 2 (85 leaves). Prefer selective re-smoke via `VISUAL_AUDIT_PRESET` / `VISUAL_AUDIT_ROUTES`, then exception surfaces only.

## Themes

| ID | Theme | Status |
|----|-------|--------|
| T1–T5 | Shared shell | **merged** (#90–#94) |
| Closeout | BACKLOG / task handoff | **merged** (#95) |
| Harness | Named presets | **merged** (#97) |
| EX-1 | My Approvals inbox | **PR #96 open** (may conflict on `task.md`) |
| EX-auth | Capture auth settle | **PR #98** — rebase onto main after #97 |

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

1. ~~Selective re-smoke~~ **Done 2026-08-10:** full catalog **84/85 PASS**; **FAIL** `/asset-models` → `/login` (harness race, not permission).
2. ~~Harness allowlist~~ **Done 2026-08-11:** `VISUAL_AUDIT_PRESET` + `docs/visual-audit/presets.json` (**#97 merged**).
3. ~~Fix `/asset-models` capture~~ **PR #98:** `requireDashboard: true` + one re-login retry; local `VISUAL_AUDIT_ROUTES=/asset-models,/asset-categories,/dashboard` → **3 PASS**. Rebase onto main after #97.
4. Resolve **#96** merge conflicts (`task.md` vs main; keep My Approvals TSX).
5. Optional multimodal / human pass on Wave 0–1 PNGs.
6. Next product: **asset profile** or dense modal (one MR from main after #96/#98).
