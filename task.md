# AI Handoff: ERP Active State

Last updated: 2026-07-30 — PR #70 residual nested-employment fix pushed (`48940ce6`).

## SESSION 2026-07-30 — Residual Employee E2E after 429 fix

**Goal**: Clear residual Playwright Employee/User E2E failures on PR #70 after rate-limit 429 was fixed.

**Current milestone**: PR #70 open — https://github.com/gmedia/erp/pull/70  
**Branch**: `fix/e2e-disable-rate-limiting`  
**HEAD**: `48940ce6`

### Prior: 429 rate-limit (done)

- Root cause: 60/min API throttle + shared admin + parallel Playwright + `APP_ENV=local`
- Fix: E2E Prepare environment appends `DISABLE_RATE_LIMITING=true` to `.env`
- Verified on run `30056540816`: **0× 429**, Quality + Test suite green
- Residual: **13 failed / 499 passed** — Employee/User dialog-not-close

### Residual root cause (done product fix)

Frontend + E2E still used flat Employee fields after Employment refactor (#68). Backend requires nested:

```json
{
  "employee_id", "name", "email", "phone",
  "current_employment": {
    "company_id", "department_id", "position_id", "branch_id",
    "salary", "hire_date", "employment_status"
  }
}
```

No companies API — company is derived from selected Branch (`company_id`).

### What landed this session (5 commits)

| Commit | Change |
|--------|--------|
| `35f8b60c` | BranchResource exposes `company_id` + unit test |
| `2b8693e0` | BranchFactory / BranchSeeder / DatabaseSeeder assign company |
| `623b76dd` | Nested Employee/Employment types + `company_id` in form schema |
| `9933861e` | EmployeeForm submits nested `current_employment`; branch `onItemSelect` sets company |
| `48940ce6` | EmployeeColumns + EmployeeViewModal read `current_employment.*` |

E2E helpers left UI-flat intentionally: form combobox labels still match Engineering / Senior Developer / Head Office / Regular; form nests payload + derives company on submit.

### Constraints that remain valid

- Do **not** invent a `testing` connection for E2E
- Do **not** re-add `skipJreProvisioning=true`
- Do **not** poll CI (one-shot only)
- Keep `.env.testing` for Pest-only; Sail/E2E use `APP_ENV=local`
- Production must never set `DISABLE_RATE_LIMITING=true`
- Prefer light sequential work (OpenCode killed by heavy parallel agents)

### Validated

- Working tree clean; branch in sync with origin
- 429 fix still present (`764fd737`)
- Product residual fix pushed to PR #70

### Open risks / next

1. One-shot CI recheck after push — confirm Employee/User E2E no longer fail on dialog-not-close
2. If still red: minimal E2E helper polish (hire_date defaults, salary edit, export expectations)
3. Optional later: CSP MinIO logo `img-src` if still noisy after suite green

### Recommended next step

```
One-shot: gh pr checks 70  (or latest run on PR #70 after headSha 48940ce6)
If Employee/User E2E green → squash-merge PR #70
If still red → inspect residual assertion (not 429) and patch helpers minimally
Do not poll CI. Do not re-add skipJreProvisioning.
```

## Continuation Prompt

```
Branch fix/e2e-disable-rate-limiting HEAD 48940ce6 on PR #70.
429 fixed; nested-employment product fix pushed (Branch company_id + EmployeeForm nested payload + columns/view).
Read task.md. One-shot CI check for PR #70. Do not poll.
If Employee/User E2E still fail, minimal helper polish only.
Do not re-add skipJreProvisioning. Prefer sequential work over heavy parallel agents.
```
