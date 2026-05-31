# Refactor Sonar Progress

Dokumen ini menyimpan status batch refactor berbasis Sonar agar prompt tetap stabil dan tidak cepat usang.

## Cara Pakai

1. Update status batch di file ini setiap selesai PR.
2. Pertahankan satu batch aktif per PR.
3. Simpan evidence singkat: metrik sebelum/sesudah + link PR.

## Batch Tracker

| Batch | Status | Scope Modul | Prioritas Teknis | Evidence |
|------|------|------|------|------|
| A | done | purchase-history-report, purchase-order-status-report, goods-receipt-report, stock-movement-report, stock-adjustment-report, inventory-valuation-report, inventory-stocktake-variance-report | report requests/resources | n/a |
| B | done | purchase-requests, purchase-orders, supplier-returns, goods-receipts, stock-adjustments, stock-movements, stock-transfers, inventory-stocktakes | filter services + item controllers | n/a |
| C | done | assets, products, asset-movements, asset-maintenances, asset-stocktakes | asset-family filter services, ProductFilterService, item controllers | snapshot 2026-04-01: duplicated_lines 6344, duplicated_blocks 332, duplicated_lines_density 7.2, coverage 87.0, new_duplicated_lines_density 11.8 |
| D | in-progress | financial-reporting | FinancialReportService + query/mapping laporan keuangan | baseline 2026-04-13 pasca-CI commit `020d6667`: duplicated_lines 0, duplicated_blocks 0, duplicated_lines_density 0.0, coverage 89.4, ncloc 71232, gate OK; snapshot Sonar pasca-CI setelah commit `4c2e275b` tetap `OK` dengan duplicated_lines 0, duplicated_blocks 0, duplicated_lines_density 0.0, coverage 89.4, ncloc 71241, new_coverage 97.2, dan new_duplicated_lines_density 0.0; snapshot Sonar pasca-CI setelah commit `62fb0f2b` tetap `OK` dengan duplicated_lines 0, duplicated_blocks 0, duplicated_lines_density 0.0, coverage 89.3, ncloc 71249, new_coverage 97.2, dan new_duplicated_lines_density 0.0; snapshot Sonar pasca-CI setelah commit `c62cb889` tetap `OK` dengan duplicated_lines 0, duplicated_blocks 0, duplicated_lines_density 0.0, coverage 89.3, ncloc 71249, new_coverage 97.2, dan new_duplicated_lines_density 0.0; local wave controller fiscal-year context helper PASS duster + targeted PHPStan + 9 feature test / 54 assertion + Playwright 6 passed; local wave comparison context helper PASS duster + targeted PHPStan + 8 feature test / 46 assertion + Playwright 6 passed; local wave comparison balance-map helper PASS duster + targeted PHPStan + 9 feature test / 50 assertion + Playwright 6 passed; local wave balance-map keyed helper PASS duster + targeted PHPStan + 10 feature test / 58 assertion + Playwright 6 passed; local wave fiscal-year coa-version resolver helper PASS duster + targeted PHPStan + 10 feature test / 58 assertion + Playwright 6 passed |
| E | done | account-mappings, journal-entries, goods-receipts, purchase-requests | pasangan Store*Request/Update*Request | baseline 2026-04-13: duplicated_lines 0, duplicated_blocks 0, duplicated_lines_density 0.0, coverage 89.3, ncloc 71150, gate OK; local wave route-aware unique rule helper untuk goods-receipts + purchase-requests PASS duster + targeted PHPStan + 21 test / 76 assertion; snapshot Sonar pasca-CI setelah commit `5ab2684c` tetap `OK` dengan duplicated_lines 0, duplicated_blocks 0, duplicated_lines_density 0.0, coverage 89.3, ncloc 71159, new_coverage 97.2, dan new_duplicated_lines_density 0.0 |
| F | done | goods-receipts, supplier-returns, inventory-stocktakes, stock-adjustments, stock-movements, stock-transfers, purchase-orders, purchase-requests, products, suppliers, customers, asset-maintenances, asset-categories, asset-locations, asset-models, imports, approval-delegations, approval-flows, pipelines, warehouses, coa-versions, fiscal-years, resources, reports, stock-monitor, assets, journal-entries, approval-audit-trail, pipeline-audit-trail, employees, accounts, account-mappings, settings | pasangan Index*/Export* request listing + export skeleton + mutation/simple CRUD request sibling + shared request rule concerns + configured backend filter groups + provider/model relation dedup + asset-family sort map helper + import row concern + asset-family cast helper + approval delegation action pair query helper + simple CRUD export filter hooks + party resource helper + report request pair helper + report index action helper + inventory report index helper + transaction export action base + timestamp export action base + import upsert helper + import row validation helper + import lookup resolution helper + import lookup preload helper + import collection helper + report export base + report export action base + transaction index action helper + master export action base + master index action helper + xlsx export action base + formatted export action base + writer export backbone + action-backed collection export base + account-mapping query helper + audit-trail index helper + remaining report index helper + journal entry index helper + asset stocktake variance export helper + base listing request helper + store/update request pair helper + item update request helper + generalized items request helper + export request helper + asset movement request pair helper + import request helper + simple crud listing helper + simple crud mutation helper + conditional rule concern helper + createMany sync action helper + upsert sync action helper + export query helper consolidation + transaction controller store helper + transaction controller update helper + controller destroy helper + controller resource relation helper + authorized form request base + abstract authorized request inheritance + crud abstract authorized request inheritance + transaction abstract authorized request inheritance + pipeline abstract authorized request inheritance + workflow abstract authorized request inheritance + asset-employee abstract authorized request inheritance + journal-stock-monitor abstract authorized request inheritance + asset-activity abstract authorized request inheritance + asset-maintenance abstract authorized request inheritance + report base authorized request inheritance + profile request authorized request inheritance + report request pair helper extension + book value report request pair helper + report filter rule helper + warehouse-purchase-order request helper + stock transfer listing helper + stock monitor request helper + asset stocktake variance request helper | snapshot 2026-04-05: duplicated_lines 4552, duplicated_blocks 245, duplicated_lines_density 5.1, coverage 0.0, ncloc 72524, new_coverage 0.0, new_duplicated_lines_density 7.2; snapshot Sonar remote terbaru via MCP setelah commit `10ee3f19` menunjukkan gate `ERROR` karena `new_coverage 0.0` dan `new_duplicated_lines_density 7.2`, sementara measures duplikasi tetap `4552/245/5.1` dengan `ncloc 72524`, local wave account-mapping query helper PASS 9 test + targeted PHPStan PASS, local wave audit-trail index helper PASS 15 test + targeted PHPStan PASS, local wave remaining report index helper PASS 7 test + targeted PHPStan PASS, local wave journal entry index helper PASS 7 test + targeted PHPStan PASS, local wave asset stocktake variance export helper PASS 3 test + targeted PHPStan PASS, local wave base listing request helper PASS 29 test + targeted PHPStan PASS, local wave base listing request helper extension PASS 19 test + targeted PHPStan PASS, local wave asset depreciation listing request helper PASS 3 test + targeted PHPStan PASS, local wave account listing request helper PASS 6 test + targeted PHPStan PASS, local wave store/update request pair helper PASS 19 test + targeted PHPStan PASS, local wave stocktake request pair helper PASS 14 test + targeted PHPStan PASS, local wave coa-version/fiscal-year request pair helper PASS 15 test + targeted PHPStan PASS, local wave fiscal year listing request helper PASS 17 test, local wave coa version listing request helper PASS 26 test, local wave controller resource relation helper PASS 29 test, local wave item update request helper PASS 25 test + targeted PHPStan PASS, local wave generalized items request helper PASS 30 test + targeted PHPStan PASS, local wave export request helper PASS 10 test + targeted PHPStan PASS, local wave export request helper extension PASS 10 test + targeted PHPStan PASS, local wave export request outlier helper PASS 9 test + targeted PHPStan PASS, local wave asset movement request pair helper PASS 11 test + targeted PHPStan PASS, local wave import request helper PASS 22 test + targeted PHPStan PASS, local wave simple crud listing helper PASS 49 test + targeted PHPStan PASS, local wave simple crud mutation helper PASS 40 test + targeted PHPStan PASS, local wave simple crud model inference helper PASS 36 test, local wave warehouse listing request helper PASS 16 test, local wave conditional rule concern helper PASS 33 test + targeted PHPStan PASS, local wave createMany sync action helper PASS 29 test + targeted PHPStan PASS, local wave upsert sync action helper PASS 22 test + targeted PHPStan PASS, local wave export query helper consolidation PASS 4 test + targeted PHPStan PASS, local wave transaction controller store helper PASS 44 test + targeted PHPStan PASS, local wave transaction controller update helper PASS 44 test + targeted PHPStan PASS, local wave controller destroy helper PASS 74 test + targeted PHPStan PASS, local wave authorized form request base PASS 65 test + targeted PHPStan PASS, local wave abstract authorized request inheritance PASS 55 test + targeted PHPStan PASS, local wave crud abstract authorized request inheritance PASS 56 test + targeted PHPStan PASS, local wave transaction abstract authorized request inheritance PASS 46 test + targeted PHPStan PASS, local wave pipeline abstract authorized request inheritance PASS 23 test + targeted PHPStan PASS, local wave workflow abstract authorized request inheritance PASS 33 test + targeted PHPStan PASS, local wave asset-employee abstract authorized request inheritance PASS 54 test + targeted PHPStan PASS, local wave journal-stock-monitor abstract authorized request inheritance PASS 19 test + targeted PHPStan PASS, local wave asset-activity abstract authorized request inheritance PASS 22 test + targeted PHPStan PASS, local wave asset-maintenance abstract authorized request inheritance PASS 12 test + targeted PHPStan PASS, local wave report base authorized request inheritance PASS 38 test + targeted PHPStan PASS, local wave profile request authorized request inheritance PASS 3 test + targeted PHPStan PASS, local wave report request pair helper extension PASS 9 test + targeted PHPStan PASS, local wave book value report request pair helper PASS 3 test + targeted PHPStan PASS, local wave report filter rule helper PASS 24 test + targeted PHPStan PASS, local wave warehouse-purchase-order request helper PASS 22 test + targeted PHPStan PASS, local wave stock transfer listing helper PASS 15 test + targeted PHPStan PASS, local wave stock monitor request helper PASS 9 test + targeted PHPStan PASS, local wave asset stocktake variance request helper PASS 7 test + targeted PHPStan PASS; post-push Sonar setelah commit `10ddd99a` menunjukkan anomali coverage `0.0` dan `new_coverage 0.0`, sementara metrik duplikasi tetap `4552/245/5.1` dan `new_duplicated_lines_density 7.2`; post-push Sonar setelah commit `77f482bf` masih menunjukkan anomali coverage `0.0` dan gate `ERROR`, sementara metrik duplikasi tetap `4552/245/5.1` dan `new_duplicated_lines_density 7.2`; post-push Sonar setelah commit `352883a5` masih menunjukkan anomali coverage `0.0` dan gate `ERROR`, sementara metrik duplikasi tetap `4552/245/5.1` dengan `ncloc 72621`; post-push Sonar setelah commit `496a0950` kembali menunjukkan snapshot coverage sehat `88.1/95.6`, sementara blocker tinggal `new_duplicated_lines_density 7.3`; post-push Sonar setelah commit `8cc54589` mempertahankan snapshot sehat `88.1/95.6` dengan `ncloc 72593`, sementara blocker tetap `new_duplicated_lines_density 7.3`; post-push Sonar setelah commit `48a575b4` mempertahankan snapshot sehat `88.1/95.6` dengan `ncloc 72585`, sementara blocker tetap `new_duplicated_lines_density 7.3`; post-push Sonar setelah commit `00e77b0c` kembali menunjukkan anomali coverage `0.0` dan `new_coverage 0.0`, sementara metrik duplikasi tetap `4552/245/5.1` dengan `ncloc 72577`; hanya `TwoFactorAuthenticationRequest` yang sengaja masih `extends FormRequest` langsung karena authorization-nya bergantung pada `Laravel\Fortify\Features::enabled(...)`, jadi bukan kandidat dedup `AuthorizedFormRequest` |
| F | done | goods-receipts, supplier-returns, inventory-stocktakes, stock-adjustments, stock-movements, stock-transfers, purchase-orders, purchase-requests, products, suppliers, customers, asset-maintenances, asset-categories, asset-locations, asset-models, imports, approval-delegations, approval-flows, pipelines, warehouses, coa-versions, fiscal-years, resources, reports, stock-monitor, assets, journal-entries, approval-audit-trail, pipeline-audit-trail, employees, accounts, account-mappings, settings | pasangan Index*/Export* request listing + export skeleton + mutation/simple CRUD request sibling + shared request rule concerns + configured backend filter groups + provider/model relation dedup + asset-family sort map helper + import row concern + asset-family cast helper + approval delegation action pair query helper + simple CRUD export filter hooks + party resource helper + report request pair helper + report index action helper + inventory report index helper + transaction export action base + timestamp export action base + import upsert helper + import row validation helper + import lookup resolution helper + import lookup preload helper + import collection helper + report export base + report export action base + transaction index action helper + master export action base + master index action helper + xlsx export action base + formatted export action base + writer export backbone + action-backed collection export base + account-mapping query helper + audit-trail index helper + remaining report index helper + journal entry index helper + asset stocktake variance export helper + base listing request helper + store/update request pair helper + item update request helper + generalized items request helper + export request helper + asset movement request pair helper + import request helper + simple crud listing helper + simple crud mutation helper + conditional rule concern helper + createMany sync action helper + upsert sync action helper + export query helper consolidation + transaction controller store helper + transaction controller update helper + controller destroy helper + controller resource relation helper + authorized form request base + abstract authorized request inheritance + crud abstract authorized request inheritance + transaction abstract authorized request inheritance + pipeline abstract authorized request inheritance + workflow abstract authorized request inheritance + asset-employee abstract authorized request inheritance + journal-stock-monitor abstract authorized request inheritance + asset-activity abstract authorized request inheritance + asset-maintenance abstract authorized request inheritance + report base authorized request inheritance + profile request authorized request inheritance + report request pair helper extension + book value report request pair helper + report filter rule helper + warehouse-purchase-order request helper + stock transfer listing helper + stock monitor request helper + asset stocktake variance request helper | baseline 2026-04-05: duplicated_lines 4552, duplicated_blocks 245, duplicated_lines_density 5.1, coverage 0.0, ncloc 72524; final snapshot 2026-04-13 after commit `4567442b`: duplicated_lines 0, duplicated_blocks 0, duplicated_lines_density 0.0, coverage 89.3, ncloc 71150, new_coverage 97.2, new_duplicated_lines_density 0.0 |

Catatan: wave dedup request untuk `approval-audit-trail` dan `pipeline-audit-trail` sudah ikut terdorong di commit sebelumnya, tetapi tetap dicatat terpisah karena berada di luar scope Batch C saat dieksekusi.

## Baseline Metrics

Isi saat mulai batch baru.

- duplicated_lines: 6344
- duplicated_blocks: 332
- duplicated_lines_density: 7.2
- ncloc: 72873
- coverage: 87.0

## Delta Metrics (setelah batch)

Isi setelah batch selesai dan sebelum merge.

- duplicated_lines: 0 (turun 4552 dari baseline Batch F)
- duplicated_blocks: 0 (turun 245 dari baseline Batch F)
- duplicated_lines_density: 0.0 (turun 5.1 dari baseline Batch F)
- ncloc: 71150 (turun 1374 dari baseline Batch F)
- coverage: 89.3


## Snapshot & Wave History

> Detail snapshot Sonar, prioritas duplikasi per-batch, rencana refactor fokus, dan log perubahan wave telah dipindahkan ke:
> **`docs/archive/refactor-sonar-progress-archive.md`**
>
> File ini hanya menyimpan batch tracker dan metrik aktif agar tetap ringkas dan mudah dibaca.

## Latest Snapshot (2026-05-31, post-wave-25)

After waves 18-25 (Sonar maintenance debt closed to floor) + CI green confirmation on `7ca7e387`:

- Quality Gate: **OK**
- ncloc: **93,096** (vs 71,249 in 2026-04-13 — codebase grown +30%)
- coverage: **94.9%**
- duplicated_lines_density: **0.7%** (831 duplicated_lines, 28 duplicated_blocks across 28 files; sub-threshold, gate OK)
- code_smells: **4** (down from 83 at start of wave 18, only deferred typescript:S3358 ternaries in BankReconciliationWorkspace remain)
- bugs: 0
- vulnerabilities: 0
- security_hotspots TO_REVIEW: 0

### OPEN issues breakdown (post-wave-25)

| Rule | Count | Severity | Effort | Notes |
|---|---|---|---|---|
| `typescript:S3358` | 4 | MAJOR | n/a | Nested ternaries in `BankReconciliationWorkspace.tsx` (lines 656-706, 815-889, 955-997, +1) — deeply nested JSX cells, refactor risk exceeds value, deferred |
| **Total** | **4** | | **n/a** | |

### Wave 22-25 retrospective

- **Closed**: 1 typescript:S6754 (wave 22), 8 orphan source files -491 LOC (wave 23), 2 php:S103 + Sonar config rule (wave 24), 2 orphan e2e helpers -265 LOC (wave 25)
- **Total LOC removed this batch**: -774 across 11 files
- **Sonar config improved**: `.sonarcloud.properties` now scopes `php:S103` exclusion to `app/Models/**` so future ide-helper regen doesn't reintroduce the noise
- **Side-effects**: zero regressions, all verifications green (Pest 85 cases / 351 assertions, E2E 31 cases, TS/ESLint/Prettier/PHPStan/Vite)

### Earlier snapshot (2026-05-31, post-wave-17)

After waves 14-17 `php:S103` sweep + dashboard rescan via Sonar API:

- Quality Gate: **OK**
- ncloc: **93,096** (vs 71,249 in 2026-04-13 — codebase grown +30%)
- coverage: **94.9%** (improved from 89.3%)
- duplicated_lines_density: **0.7%** (still low)
- code_smells: **83** (down from 197 `php:S103` lines pre-wave-14 — but new `php:S1808` introduced)
- bugs: 0
- vulnerabilities: 0

### OPEN issues breakdown (post-wave-17)

| Rule | Count | Severity | Effort | Notes |
|---|---|---|---|---|
| `php:S1808` | 45 | MINOR | 45min | Function call argument list alignment — **introduced by waves 14-17** when splitting long lines created mixed inline/newline arg patterns |
| `typescript:S4325` | 14 | MINOR | 14min | Redundant TypeScript type assertions |
| `typescript:S3358` | 8 | MINOR | 8min | Nested ternary operations |
| `typescript:S6759` | 6 | MINOR | 6min | TS lint |
| `typescript:S6606` | 3 | MINOR | 3min | TS lint |
| `typescript:S7735` | 2 | MINOR | 2min | TS lint |
| `php:S103` | 2 | MAJOR | 2min | Auto-generated by `ide-helper:models -RW` — **cannot fix manually** |
| Other TS rules | ~3 | MINOR | 3min | Various TS lint |
| **Total** | **83** | | **~165min** | |

### Wave 14-17 retrospective

- **Closed**: 195 `php:S103` violations (manually-fixable)
- **Side-effect**: 45 `php:S1808` introduced (mixed argument indentation patterns)
- **Net trade**: MAJOR severity → MINOR severity, total maintainability debt reduced

### Known gotcha

Long single-line arrow functions like `static fn (...): array => UpdateXData::fromArray(...)->toArray()` cannot be split with `=>\n` because Duster's `no_multiline_whitespace_around_double_arrow` fixer collapses them back. **Solution**: convert to multi-line function expression `static function (...): array { return ...; }`. Applied across 9 transaction controllers in wave 17.

## Next Steps

- ✅ Wave 18 (commit `9caa14cb`): cleaned up 45 `php:S1808` by splitting all promoted-property constructors and `whenLoaded()` / `array_merge()` calls into consistent multi-line argument format. Verified PHPStan + Duster + Pest groups (bank-reconciliations, period-closings, recurring-journals, stock-adjustments, stock-transfers, account-mappings, ar-receipts, reports) all green.
- ✅ Wave 19 (commit `c7ca491b`): cleaned up 12/14 `typescript:S4325` redundant type assertions across 10 files. Kept 2 in `AccountForm` (genuine bridge between zod schema and EntityForm generic - removal causes TS2322).
- ✅ Wave 20 (commit `04e0e39f`): cleaned up 4/8 `typescript:S3358` nested ternaries in `FinancialReportPageShell`, `FinancialTableReportPage`, `useEntityFormItemDialog`, and `user-guide/index.tsx` via `let content: ReactNode` + if/else pattern. Skipped 4 in `BankReconciliationWorkspace.tsx` (deeply nested JSX cells, high refactor risk).
- ✅ Wave 21: cleaned up 11/14 frontend lint issues across 9 files. Mechanical fixes for `S6759` (Readonly props ×6), `S6606` (`||`→`??` ×3), `S7735` (negated conditions ×2), `S6479` (composite stable key ×1). Skipped 1 `S6754` (semantics change risk) + 1 `S6478` (Sonar false positive on render prop closure).
- ✅ Wave 22 (commit `b12ddfc4`): closed 1 `typescript:S6754` in `BankReconciliationWorkspace.tsx`. The state was declared as `const [, setCurrentReconciledBalance] = useState<number>(...)` — destructured value omitted, only setter called from 5 sites, value never read. Removed the useState + 5 setter calls (-18 LOC). Safe because `setCurrentDifference` already triggers re-render on every same code path. Verified: TS/ESLint/Prettier clean, Pest `bank-reconciliations` 32/32 (109 assertions), E2E `bank-reconciliations` + `fiscal-year-auto-select` 17/17. **OPEN: 10 → 9.**
- ✅ Wave 23 (commits `44d20327` + `b6b708a1`): dead code audit. Audited orphan files via depwire `find_dead_code` → pivoted to scoped per-directory grep (depwire results too noisy with Laravel reflection false positives). Each candidate verified individually with broader symbol grep (catching `extends`, type annotations, named imports) and `depwire_simulate_change` (0 broken imports across all 8). Removed 8 files (-491 LOC):
  - `app/DTOs/Products/UpdateProductData.php` (initial scaffold orphan, never wired to controller)
  - `resources/js/components/common/{CheckboxField,ExportButton,ErrorBoundary,GenericDataTable,ColumnVisibilityToggle,StyledSelect}.tsx`
  - `resources/js/hooks/useEntityFilters.ts`

  Re-checked Concerns/Traits, Requests, Resources, Actions, Models, Services, Domain — all alive. Verified: `tsc --noEmit` clean, PHPStan `[OK] No errors`, ESLint full repo clean, Vite build green, E2E `dashboards` + `fiscal-year-auto-select` 8/8, Pest `products` 53/53 (242 assertions). **No Sonar metric impact directly, but removed the surface area for future false-positive issues.**
- ✅ Wave 24 (commit `f81975ad`): silenced `php:S103` on auto-generated model PHPDoc. Added `sonar.issue.ignore.multicriteria=ide_helper_long_lines` to `.sonarcloud.properties` scoping `php:S103` exclusion to `app/Models/**`. Reasoning: `ide-helper:models -RW` emits PHPDoc method/property lines that frequently exceed 120 chars (Eloquent\Builder generics + long `whereXxx` scope names). Manual line-breaks would be reverted on next regen. Also marked the 2 existing OPEN `php:S103` issues in `SubscriptionBillingRecord.php` as `accept` via Sonar API (`AZ5dn1pZle0EgQmwrVe3`, `AZ5dn1pZle0EgQmwrVe4`). **OPEN: 9 → 4.** This eliminates the "Skip 2 remaining `php:S103`" footnote — they're now properly suppressed at config level.
- ✅ Wave 25 (commit `7ca7e387`): orphan e2e test helpers + `.gitignore` hygiene. `tests/e2e/{period-closings,recurring-journals}/helpers.ts` each exported 2-3 helper functions (`createPeriodClosing`, `searchPeriodClosing`, `createRecurringJournal`, `searchRecurringJournal`, `editRecurringJournal`) but no spec ever imported them. The private `selectAsyncOption` inside both files is redundantly redefined across 12 sibling helpers.ts files — not a shared utility worth preserving. Removed both (-265 LOC). Also added `.depwire/` to `.gitignore` (depwire MCP cache, was showing up as untracked across all session commits). Verified: TS clean, depwire simulate 0 broken imports, E2E `period-closings` + `recurring-journals` 14/14 pass.
- CI verification (CI run `26715694961`, completed 2026-05-31 15:30Z): all 3 jobs green ✅ on `7ca7e387` (Quality 14:55Z, Playwright E2E 15:28Z, Test suite 15:28Z). All prior session commits cancelled by concurrency group as new pushes superseded — normal pattern, not failures.
- Sonar Security Hotspots: 0 TO_REVIEW (verified via API, 2026-05-31).
- Project health stable: gate OK, coverage 94.9%, dup density 0.7%, zero circular deps, OPEN code smells dropped 83 → 4 across waves 18-24, all 4 remaining are deferred `typescript:S3358` ternaries in `BankReconciliationWorkspace.tsx`.
