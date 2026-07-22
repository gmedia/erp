# AI Handoff: ERP Active State

Last updated: 2026-07-22 — Quality failed on Sonar Java 17; fix staged. PR #69 OPEN.

## SESSION 2026-07-22 — Unstick E2E CI (PR #69)

**Goal**: Get PR #69 fully green (Quality + Test suite; verify E2E migrate).

**Current milestone**: Fix Sonar scanner Java (blocker for Quality → blocks E2E).

### Root causes (confirmed)

1. **E2E migrate (fixed earlier)**: `--database=testing` invalid (no Laravel connection). Now `migrate:fresh --seed --force` on default `mariadb`/`laravel`.
2. **Quality Sonar (new blocker, run 29901127532)**: `sonar.scanner.skipJreProvisioning=true` made scanner use bundled JRE **Java 17**. SonarCloud rejects Java 17. Log: `Using the java executable '.../sonar-scanner-cli/.../jre/bin/java' from JAVA_HOME` then `Java 17.0.15` then ERROR upgrade to Java 21+.

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
| (pending) | fix: stop skipping Sonar scanner JRE provisioning (Java 21+) |

1. E2E migrate without `--database=testing`
2. AuthController login debug removed
3. Remove `-Dsonar.scanner.skipJreProvisioning=true` so scanner provisions supported JRE

### Validated

- Local: bad migrate flag → `Database connection [testing] not configured.`
- CI run 29901127532: Duster/PHPStan/TS/tests with coverage **passed**; Sonar scan **failed** exit 3 (Java 17)
- E2E **skipped** because Quality failed (`needs: quality`)
- Project quality gate on main: OK (not the PR analysis failure)

### Next steps

1. Commit + push Sonar JRE provisioning fix.
2. One-shot check next CI run: Quality Sonar step green.
3. Confirm E2E "Run database migrations and seed" passes.
4. If Quality + Test suite green: squash-merge PR #69. E2E has `continue-on-error: true`.

### Critical context

- Branch: `fix/e2e-login-debug`
- PR: https://github.com/gmedia/erp/pull/69
- Failed Quality job: https://github.com/gmedia/erp/actions/runs/29901127532/job/88861954670
- Do **not** re-add `skipJreProvisioning=true`
- Do **not** invent a `testing` connection for E2E
- Do **not** poll CI

### Key files

- `.github/workflows/tests.yml` — Sonar args + E2E migrate (~line 217–229, ~361)
- `app/Http/Controllers/Api/AuthController.php`
- `phpunit.xml` / `.env.example` / `tests/e2e/global-setup.ts` / `DatabaseSeeder.php`

## Continuation Prompt

```
Continue PR #69 on fix/e2e-login-debug. Read task.md.
Quality failed: Sonar used bundled Java 17 due to skipJreProvisioning.
Fix: remove that flag. Push, then one-shot check gh pr checks 69.
Do not invent testing connection. Do not poll CI. Do not re-add skipJreProvisioning.
```
