# AI Handoff: ERP Active State

Last updated: 2026-07-22 — Sonar JRE fix pushed. CI run #29902687313 IN_PROGRESS. PR #69 OPEN.

## SESSION 2026-07-22 — Unstick E2E CI (PR #69)

**Goal**: Get PR #69 fully green (Quality + Test suite; verify E2E migrate).

**Current milestone**: Wait for one-shot CI result on Sonar JRE provisioning fix (do not poll).

### Root causes (confirmed)

1. **E2E migrate (fixed earlier)**: `--database=testing` invalid (no Laravel connection). Now `migrate:fresh --seed --force` on default `mariadb`/`laravel`.
2. **Quality Sonar (fixed, awaiting CI)**: `sonar.scanner.skipJreProvisioning=true` made scanner use bundled JRE **Java 17**. SonarCloud rejects Java 17. Fix: remove the flag so scanner provisions supported JRE (Java 21+).

| Context | Connection name | Database name |
|---------|-----------------|---------------|
| Pest (`phpunit.xml`) | `mariadb` | `testing` (via `DB_DATABASE`) |
| Sail app / E2E | `mariadb` | `laravel` |

### What changed this session

| Commit | Message |
|--------|---------|
| `3285c2cd` | fix: use default DB for E2E migrate:fresh --seed |
| `44ba57c2` | fix: remove temporary AuthController login debug logging |
| `ae1f66d6` | docs: update task.md handoff for E2E CI fix |
| `71f595b0` | docs: note CI run after E2E migrate fix push |
| `03468670` | fix: keep Sonar scanner JRE provisioning for Java 21+ |
| `2e7e039c` | docs: update task.md for Sonar Quality blocker |

1. E2E migrate without `--database=testing`
2. AuthController login debug removed
3. Remove `-Dsonar.scanner.skipJreProvisioning=true` so scanner provisions supported JRE

### Validated

- Local: bad migrate flag → `Database connection [testing] not configured.`
- CI run 29901127532: Duster/PHPStan/TS/tests with coverage **passed**; Sonar scan **failed** exit 3 (Java 17)
- E2E **skipped** because Quality failed (`needs: quality`)
- Project quality gate on main: OK (not the PR analysis failure)
- Push: HEAD `2e7e039c` on `fix/e2e-login-debug` matches origin
- CI run https://github.com/gmedia/erp/actions/runs/29902687313 started; Quality checks **pending** at push time

### Next steps

1. One-shot check `gh pr checks 69` / run #29902687313 (no polling).
2. Confirm Quality Sonar step green (Java 21+).
3. Confirm E2E "Run database migrations and seed" passes.
4. If Quality + Test suite green: squash-merge PR #69. E2E has `continue-on-error: true`.

### Critical context

- Branch: `fix/e2e-login-debug`
- PR: https://github.com/gmedia/erp/pull/69
- HEAD: `2e7e039c`
- New CI run: https://github.com/gmedia/erp/actions/runs/29902687313
- Prior failed Quality: https://github.com/gmedia/erp/actions/runs/29901127532/job/88861954670
- Do **not** re-add `skipJreProvisioning=true`
- Do **not** invent a `testing` connection for E2E
- Do **not** poll CI

### Key files

- `.github/workflows/tests.yml` — Sonar args + E2E migrate (~line 217–229, ~361)
- `app/Http/Controllers/Api/AuthController.php`
- `phpunit.xml` / `.env.example` / `tests/e2e/global-setup.ts` / `DatabaseSeeder.php`

## Continuation Prompt

```
Continue PR #69 on fix/e2e-login-debug. Read task.md. HEAD should be 2e7e039c (or later).
One-shot check gh pr checks 69 / run 29902687313.
If Quality Sonar green, verify E2E migrate/login. If Sonar still fails, read Quality job logs for next root cause.
Do not invent testing connection. Do not poll CI. Do not re-add skipJreProvisioning.
```
