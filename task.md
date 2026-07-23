# AI Handoff: ERP Active State

Last updated: 2026-07-23 — APP_ENV fix applied. PR #69 OPEN on fix/e2e-login-debug.

## SESSION 2026-07-23 — Fix E2E login DB mismatch (PR #69)

**Goal**: Get PR #69 fully green (Quality + Test suite; Playwright past global-setup login).

**Current milestone**: Pushed `APP_ENV=local` fix; one-shot CI check pending.

### Root causes (confirmed)

1. **E2E migrate (fixed)**: `--database=testing` invalid. Now `migrate:fresh --seed --force` on default `mariadb`/`laravel`.
2. **Quality Sonar (fixed)**: removed `sonar.scanner.skipJreProvisioning=true` so scanner provisions Java 21+.
3. **E2E login 500 (fix applied)**: CI `cp .env.example .env` with `APP_ENV=testing` → Laravel `LoadEnvironmentVariables` loads tracked `.env.testing` → `DB_DATABASE=testing`. Migrate/seed fill `laravel`; HTTP app queries `testing.users` → 500.

| Context | Connection | Database |
|---------|------------|----------|
| Pest (`phpunit.xml`) | `mariadb` | `testing` |
| Sail app / E2E (intended) | `mariadb` | `laravel` |
| Broken HTTP path (before fix) | `mariadb` | `testing` via `.env.testing` |

### What changed this session

| Commit | Message |
|--------|---------|
| (pending) | fix: use APP_ENV=local in .env.example so E2E uses laravel DB |
| (pending) | docs: update task.md for env/DB mismatch fix |

Prior commits on branch:

| Commit | Message |
|--------|---------|
| `6a71c163` | docs: note CI run after Sonar JRE fix push |
| `03468670` | fix: keep Sonar scanner JRE provisioning for Java 21+ |
| `3285c2cd` | fix: use default DB for E2E migrate:fresh --seed |
| `44ba57c2` | fix: remove temporary AuthController login debug logging |

### Validated

- CI run 29902892175 (`headSha` `6a71c163`): Quality green (incl. Sonar); E2E migrate/seed green; Playwright failed at `createAdminAuthState` with login **500**
- Exact error: `Table 'testing.users' doesn't exist ... Database: testing ... email = admin@dokfin.id`
- Framework: `Env::get('APP_ENV')` → load `.env.{APP_ENV}` when file exists
- `artisan serve` passthrough includes `APP_ENV` to child PHP server process
- Pest isolation unchanged: `phpunit.xml` still sets `APP_ENV=testing` + `DB_DATABASE=testing`

### Next steps

1. One-shot check next CI run after push (no polling).
2. Confirm Quality still green.
3. Confirm Playwright global-setup login past 500 (no `testing.users` error).
4. If Quality + Test suite green and E2E verified: squash-merge PR #69. E2E has `continue-on-error: true`.

### Critical context

- Branch: `fix/e2e-login-debug`
- PR: https://github.com/gmedia/erp/pull/69
- Prior CI: https://github.com/gmedia/erp/actions/runs/29902892175
- Do **not** invent a `testing` connection for E2E
- Do **not** re-add `skipJreProvisioning=true`
- Do **not** poll CI
- Keep `.env.testing` for Pest-only overrides; do not point Sail/E2E at it

### Key files

- `.env.example` — `APP_ENV=local` (was `testing`)
- `.env.testing` — Pest DB `testing` (unchanged)
- `phpunit.xml` — Pest env overrides (unchanged)
- `.github/workflows/tests.yml` — `cp .env.example .env`; E2E migrate/seed
- `tests/e2e/global-setup.ts` — login bootstrap
- `app/Http/Controllers/Api/AuthController.php`

## Continuation Prompt

```
Continue PR #69 on fix/e2e-login-debug. Read task.md.
One-shot check gh pr checks 69 / latest CI run after APP_ENV=local fix.
Expect: Quality green; Playwright login no longer hits testing.users.
If still 500, re-read E2E logs for active DB name.
Do not invent testing connection. Do not poll CI. Do not re-add skipJreProvisioning.
```
