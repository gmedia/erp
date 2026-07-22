# AI Handoff: ERP Active State

Last updated: 2026-07-22 — Pushed E2E migrate fix. HEAD `ae1f66d6`. CI run #29900923800 IN_PROGRESS. PR #69 OPEN.

## SESSION 2026-07-22 — Unstick E2E CI (PR #69)

**Goal**: Get PR #69 E2E job past "Run database migrations and seed" and fully green.

**Current milestone**: Fix pushed; waiting for CI result (do not poll — check once next session).

### Root cause (confirmed)

Previous commit `73a12c9b` used invalid `--database=testing` (no such Laravel connection).

| Context | Connection name | Database name |
|---------|-----------------|---------------|
| Pest (`phpunit.xml`) | `mariadb` | `testing` (via `DB_DATABASE`) |
| Sail app / E2E (`.env` from `.env.example`) | `mariadb` | `laravel` |
| `config/database.php` | no `testing` key | — |

Local repro: `Database connection [testing] not configured.`

### What changed this session (pushed)

| Commit | Message |
|--------|---------|
| `3285c2cd` | fix: use default DB for E2E migrate:fresh --seed |
| `44ba57c2` | fix: remove temporary AuthController login debug logging |
| `ae1f66d6` | docs: update task.md handoff for E2E CI fix |

1. `.github/workflows/tests.yml` — `migrate:fresh --seed --force` (no `--database=testing`)
2. `AuthController::login()` — restored plain `Auth::attempt`; removed debug `Log::info`

### Validated

- Local: bad flag fails with connection not configured
- Push: branch `fix/e2e-login-debug` @ `ae1f66d6` matches origin
- CI: run https://github.com/gmedia/erp/actions/runs/29900923800 started; Quality checks IN_PROGRESS at push time

### Next steps

1. Check `gh pr checks 69` / run #29900923800 once (no polling).
2. Confirm E2E step "Run database migrations and seed" passes.
3. If migrate OK but login fails: seed admin `admin@dokfin.id` / `password` + Sail logs.
4. If Quality + Test suite green: squash-merge PR #69. E2E has `continue-on-error: true` — still verify manually.

### Critical context

- Branch: `fix/e2e-login-debug`
- PR: https://github.com/gmedia/erp/pull/69
- HEAD: `ae1f66d6`
- Prior failed E2E job (pre-fix): #87815025920
- `CI_SAIL_IMAGE`: `ghcr.io/gmedia/erp/ci-sail:8.4`
- Do **not** invent a `testing` connection for E2E

### Key files

- `.github/workflows/tests.yml` — E2E migrate step (~line 361)
- `app/Http/Controllers/Api/AuthController.php`
- `phpunit.xml` / `.env.example` / `tests/e2e/global-setup.ts` / `DatabaseSeeder.php`

## Continuation Prompt

```
Continue PR #69 E2E CI fix on fix/e2e-login-debug.
Read task.md. HEAD should be ae1f66d6 (or later). Check gh pr checks 69 / run 29900923800.
If E2E migrate passed, verify login; if failed, read job logs for next root cause.
Do not invent a testing connection. Do not poll CI.
```
