# P&L by Department — Pre-Implementation Research

**Status:** Read-only research, not yet committed to implementation.
**Generated:** 2026-06-01

## Executive Summary

| | |
|---|---|
| Effort sizing | **LARGE** (7+ days) for full implementation |
| Critical blocker | `journal_entry_lines` has NO `department_id` column. Schema migration + posting flow refactor required across 8 posting actions. |
| Recommended next action | **Two-phase approach**: Phase 1 (~1 day) = data model migration + opt-in population; Phase 2 (~3-5 days) = service extension + frontend. Or defer entirely if department-level P&L isn't currently a high-priority business need. |

The codebase has clean conventions to extend — `Department` model exists, `FinancialReportService::getIncomeStatement` is well-structured, posting actions follow uniform patterns. But the foundational dimension column is missing on the journal lines table. This means we cannot just "add a filter and frontend" — every source document → journal posting flow must learn to carry a department dimension.

---

## A. Schema Findings

### A.1 — `journal_entry_lines` has NO department dimension

`database/migrations/2026_01_30_000000_create_coa_tables.php:101-112`:

```php
Schema::create('journal_entry_lines', function (Blueprint $table) {
    $table->id();
    $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
    $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
    $table->decimal('debit', 15, 2)->default(0);
    $table->decimal('credit', 15, 2)->default(0);
    $table->string('memo')->nullable();
    $table->timestamps();
    $table->index('journal_entry_id');
    $table->index('account_id');
});
```

**No `department_id`, `cost_center_id`, `project_id`, OR `branch_id` exists on this table.** Same goes for the parent `journal_entries` table (`coa_tables.php:80-95`) — it carries `fiscal_year_id`, `entry_number`, `entry_date`, `reference`, `description`, `status`, `created_by`, `posted_by`, `posted_at` only.

The follow-up migration `2026_05_11_000000_add_journal_type_and_source_to_journal_entries.php` adds journal-type metadata to the parent only, not dimensions.

**Implication**: there is no existing dimension column at all. Adding department means a NEW column, not "wiring up an unused one".

### A.2 — `Department` model exists but is shallow

`app/Models/Department.php` (40 lines):

- `$fillable = ['name']` — just a name. No code, no parent department, no branch link.
- No relationships defined. The model is a leaf used for `Employee` and `PurchaseRequest` foreign keys (per migration grep: `2025_09_22_092704_create_employees_table.php`, `2026_03_05_150000_create_purchase_requests_table.php` reference `department_id`).
- Department factory exists (per `DepartmentFactory` annotation), so seeding is straightforward.

### A.3 — Where `department_id` already exists today

| Table | File | Notes |
|---|---|---|
| `employees` | `2025_09_22_092704_create_employees_table.php` | Each employee belongs to a department |
| `purchase_requests` | `2026_03_05_150000_create_purchase_requests_table.php` | PR carries department |
| `approval_flows` | `2026_02_27_035612_create_approval_flows_table.php` | Approval routing by department |
| `assets` (likely via management tables) | `2026_02_03_000000_create_asset_management_tables.php` | Asset assigned to department |

So Department is a real first-class dimension elsewhere — but never made it onto the GL.

### A.4 — No `posting_journals` separate table

The codebase uses a single `journal_entries` + `journal_entry_lines` model. There is no parallel posting journal table to retrofit; everything goes through these two tables.

---

## B. Service Findings

### B.1 — `getIncomeStatement` signature

`app/Services/FinancialReportService.php:204`:

```php
public function getIncomeStatement(int $fiscalYearId, ?int $comparisonFiscalYearId = null): array
```

Takes fiscal year IDs only. Returns `['revenues', 'expenses', 'totals']` shape with comparison context. The internals (`accountsWithPostedSums`, `prepareComparisonContext`, `collectAccountBuckets`) all key off `coa_version_id` + `fiscal_year_id` only.

### B.2 — No existing dimension filter pattern

Searched `getIncomeStatement` callers and the supporting helpers. None of them filter posted lines by anything other than fiscal year. The aggregation is `SUM(debit) - SUM(credit)` over `journal_entry_lines` joined to `accounts` joined to `journal_entries` (where status='posted' and fiscal_year_id matches).

### B.3 — Extending the signature is feasible

The helper methods (`accountsWithPostedSums` etc.) center the JOIN/SUM logic. They could accept an optional `?int $departmentId = null` and add `WHERE journal_entry_lines.department_id = ?` to the line-level subquery. But this assumes the column exists.

### B.4 — Comparable filter pattern: branch in inventory reports

`branch_id` is used as a filter dimension widely in **inventory** reports (per Oracle's prior aging review, mirrored in `IndexArAgingReportAction`). But this works because `customer_invoices`, `supplier_bills`, etc. carry `branch_id` directly. They don't go through journal lines for the filter.

There is **no existing report that filters by a journal-line-level dimension**. Implementing this is genuinely new ground for the codebase.

---

## C. Frontend Findings

### C.1 — Existing Income Statement page

The current Income Statement uses `FinancialReportPageShell` (per `docs/module-registry.md`) with a fiscal year selector. It mirrors `balance-sheet`, `cash-flow`, `comparative` reports — all FY-only.

### C.2 — Department API surface

The codebase already exposes `/api/departments` via the standard SimpleCrud Index endpoint (Department is registered as a simple CRUD module in `entityConfigs.ts` per registry). So an `AsyncSelectField` with `url="/api/departments"` would just work.

Pattern to mirror: `AsyncSelectField` with FY auto-select (per `docs/development-patterns.md`, the project's "preferred fiscal year" pattern). Apply same shape for department: optional, no auto-select, default = "All Departments".

### C.3 — Where the new filter lives

Two options:
- **Extend existing `/reports/income-statement`** — add a department dropdown next to the FY selector. Empty = current behavior unchanged. This is the minimal-disruption path.
- **Dedicated page `/reports/profit-loss-by-department`** — same data, but pivoted (departments as columns). More work, but more useful for actual P&L analysis.

---

## D. Posting Flow Findings

### D.1 — 8 posting actions create journal lines

`app/Actions/AccountingPosting/`:
- `PostArReceiptJournalAction.php`
- `PostApPaymentJournalAction.php`
- `PostBankReconciliationJournalAction.php`
- `PostCustomerInvoiceJournalAction.php`
- `PostGoodsReceiptJournalAction.php`
- `PostStockAdjustmentJournalAction.php`
- `PostSupplierBillJournalAction.php`
- `PostSupplierReturnJournalAction.php`

Plus 2 more outside that namespace:
- `app/Actions/PeriodClosings/ClosePeriodAction.php` — period-closing journal lines
- `app/Actions/AssetDepreciationRuns/PostDepreciationToJournalAction.php` — depreciation journal lines

Each action writes to `journal_entry_lines` with `'debit' => $x, 'credit' => $y, 'memo' => '...'` only. None of them carry a department dimension today, because none of their source documents do (CustomerInvoice, SupplierBill, ApPayment, ArReceipt are all branch-scoped, not department-scoped).

### D.2 — Source documents do NOT carry department

Spot-check via grep: `customer_invoices`, `supplier_bills`, `ap_payments`, `ar_receipts`, `goods_receipts` migrations don't include `department_id`. They have `branch_id`, `customer_id`/`supplier_id`, `fiscal_year_id` — but no department.

Only `purchase_requests` has `department_id`. POs derived from PRs could inherit, but the POs/Bills don't carry it forward.

### D.3 — Gap: how would `department_id` be derived per line?

For period-closing and depreciation, lines are aggregate by account — there is no natural "department" to attribute to. Real-world solutions:

- **Per-line tagging** (most flexible): user explicitly tags each line. Requires UX changes for manual journal entries; for system-posted entries needs derivation rules.
- **Per-document tagging** (cheaper): document carries `department_id`, all its journal lines inherit. Requires migrations on customer_invoices, supplier_bills, etc.
- **Per-employee derivation**: lines tied to a payroll/employee transaction inherit employee.department_id. Doesn't apply to AR/AP.
- **Default branch's department**: only useful if branches are 1:1 with departments (often not true).

**This is a genuine business modeling decision, not a coding decision.** Any pick will be partial coverage; some lines will always be "Unassigned".

---

## E. Existing Patterns

### E.1 — branch_id filter pattern in non-GL reports works because the column lives on the source table

`IndexArAgingReportAction`, `IndexInventoryValuationReportAction`, etc. filter by `branch_id` because customer_invoices.branch_id and warehouse.branch_id exist. They never need to JOIN through journal lines.

### E.2 — There is no reusable "GL dimension filter" pattern in the codebase

Adding department as a GL line dimension is genuinely new architecture. No earlier feature has paved this road.

### E.3 — General Ledger report (`GetGeneralLedgerReportAction`) confirms the missing column

This action queries `journal_entry_lines` directly. If a `department_id` column existed and were populated, GL drill-down would already be using it. It isn't — confirming this dimension is unbuilt.

---

## Recommended Architecture

### Option A — Phase 1 only: Department-tagged manual journals (~1 day)

Add `department_id` (nullable) to `journal_entry_lines` + UI + service filter. Manual `JournalEntry` form gets a per-line department picker. System-posted lines stay null. P&L by Department only includes lines that were manually tagged.

- **Pro**: Tiny lift. Unblocks the report. Honest about coverage ("lines with no department tag" bucket).
- **Con**: Most P&L lines come from system posting (invoices, bills) — those will all be null. Report will show ~0% department coverage in practice.
- **Verdict**: Low value unless the user's accounting team predominantly uses manual journal entries.

### Option B — Full implementation: Document-level department + propagation (~5-7 days)

- Migration: add `department_id` to `customer_invoices`, `supplier_bills`, `journal_entry_lines`.
- All 8 posting actions: read `department_id` from source document, write to all created journal lines.
- Form changes: AsyncSelectField for department on Customer Invoice, Supplier Bill, AP Payment, AR Receipt forms.
- Service: extend `getIncomeStatement` with optional department filter. Joined on `journal_entry_lines.department_id`.
- Frontend: extend Income Statement page with department selector.
- Tests: per-posting-action test (8x), service test, frontend integration.
- **Risk**: Existing data has all NULLs. New data flows forward. Requires user education.

### Option C — Defer (~0 days, recommended for now)

Don't build. Reasons to defer:
1. No critical business requirement surfaced (the request was "next product feature suggestion", not "we need this for finance close").
2. Requires significant business modeling: which doc types should carry department, what's the derivation when ambiguous, how to handle period closing.
3. Lower-effort high-value alternatives exist: Budget Management, Sales/Invoicing, Multi-currency.

### API contract proposal (if Option B chosen)

`GET /api/reports/income-statement?fiscal_year_id=N&comparison_fiscal_year_id=M&department_id=D` — extend existing endpoint with optional `department_id`. Empty = current behavior. This avoids endpoint sprawl.

For pivot-style "all departments side by side", a dedicated `GET /api/reports/profit-loss-by-department?fiscal_year_id=N` returning `{ departments: [...], rows: [{ account, by_department: { id_1: amt, id_2: amt, ... } }], totals: {...} }` is the right shape. But that's strictly Phase 3.

---

## Effort Breakdown (Option B)

| Task | Estimate | Notes |
|---|---|---|
| Migration: `journal_entry_lines.department_id` (nullable, FK, indexed) | 1h | + factory update |
| Migration: `customer_invoices.department_id` | 1h | |
| Migration: `supplier_bills.department_id` | 1h | |
| Update CustomerInvoice / SupplierBill forms | 4h | AsyncSelectField + validation |
| Update CustomerInvoice / SupplierBill posting actions to propagate | 3h | 2 actions |
| Update ApPayment / ArReceipt posting actions to inherit from invoice/bill | 4h | 2 actions, look up source doc dept |
| Update GoodsReceipt / SupplierReturn posting actions | 3h | 2 actions |
| Decide policy for StockAdjustment, BankReconciliation, PeriodClosing, Depreciation | 4h | Likely "always null" — document the decision |
| Service: extend `getIncomeStatement` + helpers with optional department filter | 4h | + tests |
| Frontend: department selector on Income Statement page | 3h | mirror FY selector pattern |
| Pest tests: per posting action, service | 6h | 8 actions × ~30 min each |
| E2E: Income Statement with department filter | 2h | |
| Module registry doc + AGENTS.md update | 1h | |
| **Total** | **~37h (~5 days)** | |

Add 1-2 days buffer for unexpected coupling = **5-7 days realistic**.

---

## Open Questions for User

1. **Is this driven by a real finance team request, or speculative product expansion?** If speculative, defer (Option C).
2. **What document types must carry department?** (CustomerInvoice + SupplierBill mandatory? Or only manual journals?)
3. **What's the policy for journal lines that have no clear department source?** (Default "Unassigned" bucket vs. require user to set vs. derive from branch)
4. **Pivot vs. filter UX preference?** A filtered Income Statement is cheap. A pivoted P&L-by-department report is expensive.
5. **Which feature to prioritize next?** P&L by Department vs. Budget Management vs. Multi-currency vs. Sales/Invoicing — this research suggests P&L-by-Department is the largest of the "next feature" candidates.

---

## Recommendation

**Defer P&L by Department in favor of a smaller-effort feature** unless the user can confirm question #1 (real driver) and answer questions #2-#3. If they confirm a finance need, Option B is doable in ~5-7 days but should be scoped as "Phase 1: Customer/Supplier docs carry department" before any P&L-by-Department report ships.

For the next product-feature pivot, **Budget Management** likely has higher value-to-effort ratio — it builds on the same `journal_entries` + `accounts` infrastructure without requiring a new GL dimension.
