# AI Handoff: ERP Active State

Last updated: 2026-08-04 — docs cleanup on `docs/cleanup-user-guides-and-stale-docs`.

## Current milestone

**Branch:** `docs/cleanup-user-guides-and-stale-docs`  
**Base:** `main` @ `bd573fb3` (post PR #70)  
**Goal:** Restore corrupted user guides + fix stale design/status/registry/dashboard docs.

## What changed in this session

### P0 — User guides (7 files rewritten)
Corrupted guides deleted and rewritten (Indonesian, menus/routes, workflows, FAQ):
- `docs/user-guide-purchase-requests.md`
- `docs/user-guide-purchase-orders.md`
- `docs/user-guide-goods-receipts.md`
- `docs/user-guide-supplier-returns.md`
- `docs/user-guide-stock-transfers.md`
- `docs/user-guide-asset-maintenances.md`
- `docs/user-guide-asset-stocktakes.md`

### P1 — Status / design truth
- `docs/budget-management-design.md` — removed GREENFIELD; marked Implemented + E2E gap
- `docs/database/IMPLEMENTATION_STATUS.md` — date 2026-08-04; rows 19–23 (Budget + dashboards); test baseline note
- `docs/refactor-sonar-progress.md` — **FROZEN**; superseded OPEN-83 section; closed Next Steps

### P2 — Registry / index / dashboards
- `docs/module-registry.md` — Last updated + correct count notes (Pest 74); dashboards note fixed
- `docs/README.md` — **new** docs index + dashboard matrix
- `docs/user-guide-dashboard.md` — rewrite for real home dashboard (4 entity totals)
- `docs/user-guide-financial-dashboard.md` — disambiguation callout vs `/dashboard`

## Validated

- Docs-only branch; no app code changes
- Guides served by `UserGuideController` glob `docs/user-guide-*.md`
- Main dashboard ground truth: `resources/js/pages/dashboard.tsx` → `GET /api/dashboard` totals

## Open risks / blockers

- Budget E2E still missing (`tests/e2e/budgets/`) — product gap, out of scope for this docs PR
- Lean rewrites of 7 guides are shorter than peer guides; expand later if product needs more depth
- Prefer light sequential tool use (heavy parallel agents killed OpenCode session)

## Recommended next step

1. Commit all docs changes on this branch
2. Push + open PR with handoff template
3. Do **not** wait for CI; move on

## Continuation Prompt

```
Continue docs cleanup on docs/cleanup-user-guides-and-stale-docs.
If uncommitted: commit, push, gh pr create.
If PR open: process open MRs per AGENTS.md (merge if green).
Do not re-do P0–P2 unless verification fails.
```
