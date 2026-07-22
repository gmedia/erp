# AI Handoff: ERP Active State

Last updated: 2026-07-22 — Fixed E2E CI migrate step (invalid `--database=testing`). HEAD pending push on `fix/e2e-login-debug`. PR #69 OPEN.

## SESSION 2026-07-22 — Unstick E2E CI (PR #69)

**Goal**: Get PR #69 E2E job past "Run database migrations and seed" and fully green.

### Root cause (confirmed)

Previous commit `73a12c9b` changed the E2E migrate step to:

```bash
./vendor/bin/sail artisan migrate:fresh --seed --database=testing
```

That is **wrong**. Laravel has **no** connection named `testing`.

| Context | Connection name | Database name |
|---------|-----------------|---------------|
| Pest (`phpunit.xml`) | `mariadb` | `testing` (via `DB_DATABASE`) |
| Sail app / E2E (`.env` from `.env.example`) | `mariadb` | `laravel` |
| `config/database.php` | no `testing` key | — |

Local repro: `Database connection [testing] not configured.` (`DatabaseManager.php:221`).

`docker-compose.yml` already mounts `docker/mariadb/create-testing-database.sh` into MariaDB initdb for the **Pest** DB. E2E hits the running app on default `laravel` DB. `tests/e2e/global-setup.ts` also runs `migrate:fresh` + `db:seed` before Playwright.

### Changes this session

1. `.github/workflows/tests.yml` — E2E migrate step:
   - from: `migrate:fresh --seed --database=testing`
   - to: `migrate:fresh --seed --force`
2. `app/Http/Controllers/Api/AuthController.php` — removed temporary login debug `Log::info` + unused `Log` import.

### Prior handoff correction

Earlier session notes claimed `config/database.php` got a `testing` connection, Dockerfile SQL init, and a sed fix of the migrate step. **None of that was in the working tree.** Only the broken `--database=testing` flag was committed (`73a12c9b`).

### Next steps

1. Push commit → CI auto-runs on PR #69.
2. Watch E2E job "Run database migrations and seed".
3. If migrate passes but login still fails: inspect Sail logs / seed admin (`admin@dokfin.id` / `password` from `DatabaseSeeder` + `APP_ADMIN`).
4. If green: squash-merge PR #69 (after Quality + Test suite still green). E2E has `continue-on-error: true` so merge may not be blocked by E2E alone — still verify manually.

### Critical context

- Branch: `fix/e2e-login-debug`
- PR: https://github.com/gmedia/erp/pull/69
- Last known CI head: `2bec9cb9` — E2E failed at migrate step (job #87815025920)
- `CI_SAIL_IMAGE`: `ghcr.io/gmedia/erp/ci-sail:8.4`
- Docker login + pull fallback to `8.4` already works in CI
- MariaDB volume wiped each E2E start: `docker volume rm erp_sail-mariadb`
- No remote `develop` — `origin/HEAD` → `main`

### Key files

- `.github/workflows/tests.yml` — E2E migrate step (~line 361)
- `phpunit.xml` — Pest uses `DB_CONNECTION=mariadb` + `DB_DATABASE=testing`
- `.env.example` — `DB_DATABASE=laravel`, `APP_ADMIN=admin@dokfin.id`
- `docker/mariadb/create-testing-database.sh` — creates Pest DB only
- `tests/e2e/global-setup.ts` — migrate/seed + login for auth state
- `database/seeders/DatabaseSeeder.php` — seeds `admin@dokfin.id` / `password`

## Continuation Prompt

```
Continue PR #69 E2E CI fix on fix/e2e-login-debug.
Read task.md. Verify migrate step is `migrate:fresh --seed --force` (no --database=testing).
Check gh pr checks 69. If E2E still fails after migrate, debug login/seed next. Do not invent a testing connection.
```
