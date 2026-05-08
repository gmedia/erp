# AI Handoff Archive: E2E Stabilization

Last updated: 2026-04-29

Read `task.md` first for the active handoff state.
This file stores condensed historical checkpoints that were moved out of `task.md` so the active handoff document stays short and current.

## 2026-04-29

- Committed the full stabilization wave as `9ea4b5582591bd106dfd84545d281c3a52d40681` (`Stabilize E2E login flow and smoke waves`).
- Main committed surfaces:
  - handoff workflow docs and reusable prompts
  - `package.json` smoke-wave dedup and explicit wave entrypoints
  - non-dashboard login rollout plus `ensureAppOrigin()` restoration in shared E2E helpers
  - seeded entity-state repair in `PipelineSampleDataSeeder.php` and the entity-state Playwright specs
  - heavy CRUD and purchasing helper or spec determinism updates
- Validation highlights:
  - `./vendor/bin/sail npm run test:e2e:smoke-waves` -> `wave:reports` `6 passed`, `wave:mixed-heavy` `4 passed`, `wave:heavy-crud` `54 passed`, `wave:purchasing` `96 passed`
  - `./vendor/bin/sail npm run test:e2e:wave:heavy-crud` -> `54 passed (3.9m)`
  - `./vendor/bin/sail npm run test:e2e:wave:purchasing` -> `96 passed (4.8m)`
  - `./vendor/bin/sail run sh ./scripts/e2e-with-lock.sh npx playwright test tests/e2e/admin-settings/admin-settings.spec.ts --workers=1 --retries=0 --reporter=line` -> `11 passed (45.5s)`
  - `./vendor/bin/sail run sh ./scripts/e2e-with-lock.sh npx playwright test tests/e2e/purchase-history-report/purchase-history-report.spec.ts --workers=1 --retries=0 --reporter=line` -> `1 passed (16.3s)`
  - `./vendor/bin/sail run sh ./scripts/e2e-with-lock.sh npx playwright test tests/e2e/entity-state-actions/entity-state-actions.spec.ts --workers=1 --retries=0 --reporter=line` -> `1 passed (16.2s)`
  - `./vendor/bin/sail run sh ./scripts/e2e-with-lock.sh npx playwright test tests/e2e/entity-state-timeline/entity-state-timeline.spec.ts --workers=1 --retries=0 --reporter=line` -> `1 passed (19.8s)`
- Persistent notes:
  - `task.*` handoff continuity remains local to this workspace because `.gitignore` excludes it.
  - Keep exactly one Playwright process active at a time because `global-setup.ts` still runs `migrate:fresh` and `db:seed`.
  - The remaining plain `login(page)` callers are intentionally limited to dashboard or debug-only surfaces.

## 2026-04-28

- Shared CRUD factory navigation trim was measured on a heavier multi-module slice without any new code change.
- Locked validation result:
  - `./vendor/bin/sail run sh ./scripts/e2e-with-lock.sh npx playwright test tests/e2e/branches/branch.spec.ts tests/e2e/departments/department.spec.ts tests/e2e/positions/position.spec.ts tests/e2e/units/unit.spec.ts tests/e2e/warehouses/warehouse.spec.ts tests/e2e/supplier-categories/supplier-category.spec.ts tests/e2e/customer-categories/customer-category.spec.ts tests/e2e/product-categories/product-category.spec.ts tests/e2e/customers/customer.spec.ts tests/e2e/suppliers/supplier.spec.ts tests/e2e/employees/employee.spec.ts --grep "datatable has correct checkbox behavior|can sort .* by all columns" --workers=1 --retries=0 --reporter=line` -> `22 passed (1.9m)`

## 2026-04-27

- Added and validated reusable chat-side wave scripts in `package.json`:
  - `test:e2e:wave:reports` -> `6 passed (26.9s)`
  - `test:e2e:wave:mixed-heavy` -> `22 passed (1.8m)`
  - `test:e2e:wave:heavy-crud` -> `54 passed (4.3m)`
  - `test:e2e:wave:purchasing` -> `102 passed (5.0m)`
  - `test:e2e:smoke-waves` -> exit code `0`
- Confirmed latest user-side full E2E rerun stayed green: `398 passed (35.0m)`.
- Refreshed cross-laptop handoff baseline on the same remote workspace without changing helper/spec behavior.
- Measured the first low-risk shared factory trim on a simple factory consumer:
  - `./vendor/bin/sail npx playwright test tests/e2e/branches/branch.spec.ts --grep "Branches datatable has correct checkbox behavior|can sort Branches by all columns" --workers=1 --retries=0 --reporter=line` -> `2 passed (20.4s)`
- Established the concurrency root cause for noisy broad failures:
  - overlapping Playwright runs were unsafe because `tests/e2e/global-setup.ts` executes `migrate:fresh` and `db:seed`
  - lock discipline via `scripts/e2e-with-lock.sh` became the authoritative chat-side execution pattern

## 2026-04-26

- Preserved green full-suite stability while expanding controlled `workers=2` validation in waves.
- Major validated subset milestones:
  - `194 passed (12.3m)` purchasing + stock/inventory + stock-adjustments + account-mappings + products
  - `232 passed (13.5m)` additive asset movement/stocktake subset
  - `326 passed (17.9m)` broader asset lane
  - `372 passed (19.8m)` approval-expanded subset
  - `416 passed (22.0m)` approval + pipeline/workflow subset
  - `560 passed (26.5m)` above subset + master-data core
- Key helper/spec hardening themes:
  - stronger stock-adjustment create confirmation
  - persisted warehouse confirmation in stock-transfer and inventory-stocktake helpers
  - delayed row/search/dialog confirmation under parallel load
  - purchasing lane stabilized with seeded `Piece` unit and `Executive Office Desk` product
- Entity-state specs were refactored to stop mutating global asset pipeline configuration during `beforeAll`.
- Minimal stock-transfer sort hotfix landed by decoupling sorting from create precondition using `skipCreateBeforeSort`.
- User-side full E2E reconfirmed green after the hotfix:
  - `./vendor/bin/sail npm run test:e2e` -> `398 passed (35.5m)`

## 2026-04-23

- Canary foundation and handoff workflow were introduced.
- New commits in that stream:
  - `a39f3819` - `test(e2e): force built assets and harden create confirmations`
  - `5e18ab62` - `chore(e2e): prevent overlapping canary runs`
- Validation snapshot:
  - `./vendor/bin/sail npm run test:e2e:canary` -> lane-a `389 passed`, lane-b `9 passed`, exit code `0`
  - `./vendor/bin/sail npm run test:e2e:canary:lane-b` -> `9 passed`, exit code `0`
- Continuity assets added:
  - `.github/prompts/continue-progress.prompt.md`
  - `.github/prompts/checkpoint-progress.prompt.md`
  - `.github/skills/session-handoff/SKILL.md`
  - `.github/skills/session-handoff/resources/handoff.template.md`

## Persistent Historical Notes

- Do not run the full E2E suite in chat; full suite remains user-side.
- Use Sail for every runtime command.
- Keep strict synchronization direction.
- Keep exactly one Playwright process active at a time.
- Shared runtime hotspots historically concentrated around:
  - `tests/e2e/helpers.ts`
  - `tests/e2e/shared-test-factories.ts`
  - `tests/e2e/stock-adjustments/helpers.ts`
  - `tests/e2e/stock-transfers/helpers.ts`
  - `tests/e2e/inventory-stocktakes/helpers.ts`

## Template Checkpoint Baru

Gunakan format ringkas berikut saat memindahkan checkpoint yang sudah tidak aktif dari `task.md` ke file arsip ini.

```md
## YYYY-MM-DD

- Milestone utama sesi itu.
- Perubahan penting yang masih relevan sebagai konteks historis.
- Validasi kunci yang perlu diingat:
  - `command` -> `outcome`
- Risiko atau batasan yang masih berlaku lintas sesi.
```

Simpan hanya checkpoint yang sudah tidak aktif. Status aktif dan next action tetap berada di `task.md`.
