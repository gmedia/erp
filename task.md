# AI Handoff: ERP Active State

Last updated: 2026-07-24 — branch `fix/e2e-disable-rate-limiting` (E2E 429 fix).

## SESSION 2026-07-24 — Playwright E2E 429 rate-limit fix

**Goal**: Stop residual Playwright CI failures caused by HTTP 429 under parallel suite load (post PR #69 merge).

**Current milestone**: Fix implemented on branch; PR open/pending.

### Root cause

1. API middleware always applies `ThrottleRequests:api` (`bootstrap/app.php`).
2. `AppServiceProvider` registers `api` limiter at **60/min per user id or IP**, unless:
   - `app()->environment('testing')`, or
   - `config('app.disable_rate_limiting')` is truthy (`DISABLE_RATE_LIMITING` env).
3. After PR #69, E2E uses `APP_ENV=local` (correct for DB), so `testing` env bypass no longer applies.
4. Playwright suite shares one admin user + parallel workers → exceeds 60 API req/min → widespread 429 → timeouts.
5. Infrastructure already existed (`config/app.php` + `Limit::none()`), but **CI E2E never set** `DISABLE_RATE_LIMITING=true`.

Evidence: job `89345123775` on run `30047647276` — many `429 (Too Many Requests)` console errors after successful global-setup login.

### Fix (minimal)

| File | Change |
|------|--------|
| `.github/workflows/tests.yml` | E2E **Prepare environment** appends `DISABLE_RATE_LIMITING=true` to `.env` (Sail app process must read it; GHA job env alone is not enough) |
| `.env.example` | Document optional `DISABLE_RATE_LIMITING` for local E2E |

Quality/Test suite jobs are **unchanged** (rate limits stay on).

### Constraints that remain valid

- Do **not** invent a `testing` connection for E2E
- Do **not** re-add `skipJreProvisioning=true`
- Do **not** poll CI
- Keep `.env.testing` for Pest-only; Sail/E2E use `.env` from `.env.example` with `APP_ENV=local`
- Production must never set `DISABLE_RATE_LIMITING=true`

### Prior session (PR #69) — closed

- Merge commit: `304f0a78` on `main`
- Fixed: invalid E2E migrate DB, Sonar JRE, login 500 (`APP_ENV=testing` → wrong DB)
- Residual after merge: this 429 issue

### Validated / expected

- Local change is workflow + docs only (no PHP behavior change unless env set)
- Next: push branch, open PR, one-shot CI check (do not poll)

### Open risks

- Residual non-429 failures may remain (e.g. CSP blocking MinIO logo `img-src`)
- Fortify login limiter (5/min) is separate; E2E uses preloaded auth state so usually not hit

### Recommended next step

```
Push fix/e2e-disable-rate-limiting, open PR, one-shot gh pr checks.
If E2E still red without 429, investigate CSP/MinIO branding next.
```

## Continuation Prompt

```
Branch fix/e2e-disable-rate-limiting: DISABLE_RATE_LIMITING=true for E2E CI only.
Read task.md. Push if not pushed, open PR if missing, one-shot CI check.
Do not poll CI. Do not re-add skipJreProvisioning.
```
