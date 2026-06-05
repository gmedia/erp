# Budget Management Module — Pre-Implementation Design

**Generated:** 2026-06-05
**Status:** Research complete, awaiting implementation decision
**Branch:** main

## Executive Summary

Budget Management is **GREENFIELD** — zero existing code. The module will let finance teams set per-account spending targets per fiscal year period, then track actual-vs-budget variance in real time. Implementation reuses the existing `FinancialReportService`, `Account`, `FiscalYear`, and `JournalEntry` infrastructure.

**Estimated effort:** 3–4 days (schema + backend + frontend + tests)
**Value:** High — enables proactive financial control instead of reactive GL review.

---

## 1. Data Model (Schema)

### 1.1 `budgets` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `ulid` | char(26) unique | Public identifier |
| `fiscal_year_id` | FK → fiscal_years | Budget period scope |
| `name` | varchar(255) | e.g. "Q1 2026 Operating Budget" |
| `description` | text nullable | |
| `budget_type` | enum: operational, capital, project, revenue | Determines sign logic |
| `status` | enum: draft, approved, locked, cancelled | Lifecycle |
| `total_amount` | decimal(15,2) default 0 | Denormalized sum of lines |
| `approved_by` | FK → users nullable | Who approved |
| `approved_at` | timestamp nullable | |
| `created_by` | FK → users | |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 1.2 `budget_lines` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `budget_id` | FK → budgets | |
| `account_id` | FK → accounts | GL account targeted |
| `period_start` | date | Period range start |
| `period_end` | date | Period range end |
| `allocated_amount` | decimal(15,2) | Budget target for this period |
| `notes` | text nullable | |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Unique constraint:** `(budget_id, account_id, period_start)` — prevents duplicate allocations.

### 1.3 Why row-per-period (not 12-column grid)

All 3 reference ERPs (Kezi, Centrex, Nexus) use date-range rows. Benefits:
- Supports non-calendar fiscal years (e.g. April–March)
- Allows quarterly or custom period granularity without schema change
- Mid-year amendments = new row or UPDATE existing
- Simpler queries (WHERE period_start/period_end overlaps)

---

## 2. Variance Calculation

### Formula

```
Available = Allocated - Actual
Variance % = ((Allocated - Actual) / Allocated) * 100
```

Where:
- **Allocated** = `budget_lines.allocated_amount` for matching account + period
- **Actual** = Sum of posted `journal_entry_lines` (debit or credit, sign-aware) for the same account within the period date range

### Sign Logic (GL account type)

```php
// Expense/Asset accounts: actual = debit - credit (positive = spent)
// Revenue/Liability/Equity accounts: actual = credit - debit (positive = earned/owed)
$signMultiplier = in_array($account->type, ['expense', 'asset']) ? 1 : -1;
$actual = ($totalDebit - $totalCredit) * $signMultiplier;
```

### Computation Strategy: **Live Query** (not materialized)

All reference ERPs compute variance at query time. Rationale:
- Journal entries can be un-posted or amended
- Budget allocations can be revised
- Materialized columns drift unless event-driven refresh is implemented
- Performance acceptable with proper indexes (account_id + entry_date + status)

### Existing Infrastructure to Reuse

| Need | Existing Method | File |
|------|----------------|------|
| Sum posted lines per account per FY | `accountsWithPostedSums()` | `app/Services/FinancialReportService.php:711` |
| Balance for date range | `Account::balanceForPeriod($start, $end)` | `app/Models/Account.php:138` |
| FY resolution | `GetPreferredFiscalYearAction` | `app/Actions/FiscalYears/` |
| Monthly trend bucketing pattern | `getMonthlyTrends()` | `app/Services/FinancialReportService.php:70` |

### New Method Needed

```php
// app/Services/FinancialReportService.php (or new BudgetVarianceService)
public function getAccountActualForPeriod(int $accountId, Carbon $start, Carbon $end, int $fiscalYearId): float
{
    // Sum posted journal_entry_lines for account within date range
    // Apply sign logic based on account type
}
```

---

## 3. Budget Lifecycle (Status Machine)

```
draft → approved → locked → (cancelled at any pre-locked stage)
```

| Transition | Trigger | Guard |
|------------|---------|-------|
| draft → approved | Manager approval | Approval flow integration (optional) |
| approved → locked | Period close or manual lock | Prevents edits |
| any → cancelled | User action | Soft state, preserves audit trail |

**Versioning:** Status-based locking + amendment pattern (no separate version table). If a locked budget needs revision, create a new Budget record referencing the original.

---

## 4. API Contract

### Endpoints

| Method | Path | Permission | Description |
|--------|------|-----------|-------------|
| GET | `/api/budgets` | `budget` | List budgets (paginated, filterable) |
| GET | `/api/budgets/{budget}` | `budget` | Show budget + lines |
| POST | `/api/budgets` | `budget.create` | Create budget with lines |
| PUT | `/api/budgets/{budget}` | `budget.edit` | Update budget + lines |
| DELETE | `/api/budgets/{budget}` | `budget.delete` | Cancel/delete draft budget |
| POST | `/api/budgets/export` | `budget` | Export to XLSX |
| POST | `/api/budgets/{budget}/approve` | `budget.approve` | Approve budget |
| POST | `/api/budgets/{budget}/lock` | `budget.approve` | Lock budget |
| GET | `/api/reports/budget-variance` | `budget_variance_report` | Variance report |
| POST | `/api/reports/budget-variance/export` | `budget_variance_report` | Export variance |

### Budget Variance Report Response

```json
{
  "data": [
    {
      "account_id": 42,
      "account_code": "5010",
      "account_name": "Office Supplies",
      "account_type": "expense",
      "allocated": 10000.00,
      "actual": 7500.00,
      "available": 2500.00,
      "variance_percent": 25.0,
      "status": "within_budget"
    }
  ],
  "summary": {
    "total_allocated": 500000.00,
    "total_actual": 375000.00,
    "total_available": 125000.00,
    "overall_variance_percent": 25.0
  },
  "meta": {
    "fiscal_year_id": 1,
    "budget_id": 5,
    "period_start": "2026-01-01",
    "period_end": "2026-03-31"
  }
}
```

---

## 5. Frontend Approach

### Pages

| Route | Component | Pattern |
|-------|-----------|---------|
| `/budgets` | `BudgetCrudPage` | `createComplexEntityConfig` + `createEntityCrudPage` |
| `/reports/budget-variance` | `BudgetVarianceReportPage` | `ReportDataTablePage` |

### Budget Form UI

Master-detail layout:
- **Header:** Name, FY selector (with preferred auto-select), budget type, description
- **Lines grid:** Account selector + period start/end + allocated amount (inline add/remove rows)
- Pattern: follows `JournalEntryForm` / `PurchaseOrderForm` with `StoresItemsInTransaction` backend

### Variance Report UI

Standard `ReportDataTablePage` with:
- Filters: fiscal_year_id, budget_id, account_type, period range
- Columns: Account Code, Account Name, Type, Allocated, Actual, Available, Variance %, Status badge
- Color coding: green (within budget), amber (>80% spent), red (over budget)

---

## 6. Implementation Phases

### Phase 1: Schema + Models (0.5 day)
- Migration: `budgets` + `budget_lines`
- Models: `Budget`, `BudgetLine` with relationships
- Factory: `BudgetFactory`, `BudgetLineFactory`
- Permission seeder entries: `budget`, `budget.create`, `budget.edit`, `budget.delete`, `budget.approve`, `budget_variance_report`
- Menu seeder entry under Accounting group

### Phase 2: Backend CRUD + Variance Service (1.5 days)
- Controller: `BudgetController` (CRUD + approve + lock)
- Action: `StoreBudgetAction`, `UpdateBudgetAction`
- Service: `BudgetVarianceService` (getActualsForPeriod, calculateVariance)
- Request: `StoreBudgetRequest`, `UpdateBudgetRequest`
- Resource: `BudgetResource`, `BudgetCollection`
- Export: `BudgetExport`, `BudgetVarianceExport`
- Route file: `routes/api/budgets.php` with `permission:budget,true`

### Phase 3: Frontend (1 day)
- Entity config in `entityConfigs.ts`
- Page: `pages/budgets/index.tsx`
- Siblings: `BudgetColumns.tsx`, `BudgetFilters.tsx`, `BudgetForm.tsx`, `BudgetViewModal.tsx`
- Report page: `pages/reports/budget-variance/index.tsx`
- Route registration in `app-routes.tsx`

### Phase 4: Tests + Integration (0.5 day)
- Pest: `BudgetControllerTest`, `BudgetExportTest`, `BudgetVarianceReportTest` + model unit tests
- E2E: `tests/e2e/budgets/` (9 standard cases)
- Optional: approval flow integration (if `ApprovalFlow` already configured for Budget entity)

---

## 7. Edge Cases & Pitfalls

| Risk | Mitigation |
|------|-----------|
| Double-counting (PO committed + JE actual) | Phase 1 tracks only posted JE actuals. Commitment tracking (PO-level pre-allocation) deferred to Phase 2 enhancement. |
| GL sign logic (debit vs credit accounts) | Sign multiplier based on `account.type`. Expense/Asset = debit-credit; Revenue/Liability/Equity = credit-debit. |
| COA version mismatch across fiscal years | Budget stores `fiscal_year_id` → resolves COA version at query time via existing `resolveRequiredCoaVersion()`. |
| Period boundary inclusivity | Use `whereBetween('entry_date', [$start, $end])` with inclusive ends. Match `Account::balanceForPeriod()` pattern. |
| Budget revision after lock | Create new Budget record (amendment) referencing original. Original stays locked for audit. |
| Currency mismatch | Deferred — current system is single-currency. Multi-currency enhancement tracked separately (Oracle H3). |
| Zero-allocated lines (division by zero in variance %) | Guard: `variance_percent = allocated > 0 ? ((allocated - actual) / allocated) * 100 : null`. |

---

## 8. Reference Projects

| Project | Relevance | Key Pattern |
|---------|-----------|-------------|
| [Kezi ERP](https://github.com/Xoshbin/kezi-ERP) | Laravel 12, double-entry, analytic accounts | `BudgetControlService.getActuals()` with GL sign multiplier |
| [Centrex Accounting](https://github.com/centrex/laravel-accounting) | Laravel package, simple Budget model | `BudgetItem::loadSpentAmounts()` bulk N+1 mitigation |
| [Nexus Budget](https://github.com/azaharizaman/nexus-budget) | Enterprise PHP package | Event-driven variance, budget types, rollover policies |

**Consensus across all 3:**
- Variance computed live (not materialized)
- Row-per-period date ranges (not 12-column grid)
- Status-based locking (draft → approved → locked)
- No separate version table (amendment records instead)

---

## 9. Decision Points for User

Before implementation starts, confirm:

1. **Period granularity** — Monthly (12 lines per account per FY) or quarterly (4 lines)? Or allow both?
2. **Approval flow** — Should budget approval use existing `ApprovalFlow` engine, or simple manager-approve pattern?
3. **Commitment tracking** — Track PO-level pre-allocations (complex) or only posted JE actuals (simpler, Phase 1)?
4. **Budget types** — Start with all 4 (operational, capital, project, revenue) or just operational?
5. **Report scope** — Variance by account only, or also by department/branch dimension?

**Recommendation:** Start with monthly periods, simple approve (no flow engine), posted actuals only, operational type only. Expand incrementally.
