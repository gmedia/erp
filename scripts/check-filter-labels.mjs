import { readdirSync, readFileSync } from 'node:fs';
import { dirname, join, relative, resolve } from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

/**
 * Guard i18n label filter (Issue #599): label dan placeholder field filter di
 * *Filters.tsx wajib lewat kunci i18n ({ key, fallback }), bukan literal English
 * seperti 'Branch' atau 'Select a category' yang tampil apa adanya di locale id.
 *
 * Allowlist di bawah adalah kelompok yang BELUM dikerjakan (utang sementara).
 * Hapus berkas dari allowlist setiap kali kelompoknya sudah diterjemahkan.
 */

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const componentsDir = resolve(root, 'resources/js/components');

const pendingFiles = new Set([
    'account-mappings/AccountMappingFilters.tsx',
    'approval-audit-trail/Filters.tsx',
    'approval-delegations/ApprovalDelegationFilters.tsx',
    'approval-flows/ApprovalFlowFilters.tsx',
    'aging-dashboard/AgingFilters.tsx',
    'asset-maintenances/AssetMaintenanceFilters.tsx',
    'asset-movements/AssetMovementFilters.tsx',
    'asset-stocktakes/AssetStocktakeFilters.tsx',
    'bank-reconciliations/BankReconciliationFilters.tsx',
    'budgets/BudgetFilters.tsx',
    'coa-versions/CoaVersionFilters.tsx',
    'credit-notes/CreditNoteFilters.tsx',
    'customer-invoices/CustomerInvoiceFilters.tsx',
    'fiscal-years/FiscalYearFilters.tsx',
    'goods-receipts/GoodsReceiptFilters.tsx',
    'inventory-stocktakes/InventoryStocktakeFilters.tsx',
    'journal-entries/JournalEntryFilters.tsx',
    'period-closings/PeriodClosingFilters.tsx',
    'pipeline-audit-trail/Filters.tsx',
    'pipelines/PipelineFilters.tsx',
    'purchase-orders/PurchaseOrderFilters.tsx',
    'purchase-requests/PurchaseRequestFilters.tsx',
    'recurring-journals/RecurringJournalFilters.tsx',
    'report-configurations/ReportConfigurationFilters.tsx',
    'security-logs/Filters.tsx',
    'stock-adjustments/StockAdjustmentFilters.tsx',
    'stock-monitor/Filters.tsx',
    'stock-movements/Filters.tsx',
    'stock-transfers/StockTransferFilters.tsx',
    'supplier-bills/SupplierBillFilters.tsx',
    'supplier-returns/SupplierReturnFilters.tsx',
    'reports/general-ledger/Filters.tsx',
    'reports/inventory-valuation/Filters.tsx',
    'reports/purchase-order-status/Filters.tsx',
    'reports/trial-balance-detailed/Filters.tsx',
    'reports/asset-stocktake-variances/Filters.tsx',
    'reports/ar-outstanding-report/Filters.tsx',
    'reports/stock-movement/Filters.tsx',
    'reports/customer-statement-report/Filters.tsx',
    'reports/ap-aging-report/Filters.tsx',
    'reports/ar-aging-report/Filters.tsx',
    'reports/inventory-stocktake-variance/Filters.tsx',
    'reports/ap-payment-history-report/Filters.tsx',
    'reports/ap-outstanding-report/Filters.tsx',
    'reports/stock-adjustment/Filters.tsx',
    'reports/budget-variance/Filters.tsx',
    'reports/goods-receipt/Filters.tsx',
    'reports/purchase-history/Filters.tsx',
    'reports/maintenance-cost/Filters.tsx',
]);

// Literal English label/placeholder: 'Branch', 'Select a category', 'All Statuses'.
// Kode ISO mata uang (IDR/USD/EUR) bukan teks yang diterjemahkan, jadi dilewati.
const englishLiteral = /^(?:[A-Z][a-zA-Z]*)(?:\s+[A-Za-z][a-zA-Z]*)*$|^(?:Select|All)\b/;
const isoCurrencyCode = /^[A-Z]{3}$/;

const literalPatterns = [
    /\b(?:label|placeholder)\s*:\s*'([^']*)'/g,
    /\b(?:label|placeholder)\s*(?::\s*[\w<>[\]| ]+)?\s*=\s*'([^']*)'/g,
    /\bcreate[A-Za-z]*FilterField\(\s*'[^']*'\s*,\s*'([^']*)'/g,
    /\bcreate[A-Za-z]*FilterField\(\s*'([^']*)'/g,
];

function walk(dir) {
    return readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
        const path = join(dir, entry.name);
        if (entry.isDirectory()) {
            return walk(path);
        }
        return /Filters?\.tsx$/.test(entry.name) ? [path] : [];
    });
}

const violations = [];
const scanned = [];

const sharedHelper = resolve(componentsDir, 'common/filters.tsx');

const pendingHelperFunctions = new Set([
    'createSimpleEntityFilterFields',
    'createSelectFilterField',
    'createTextFilterField',
    'createAsyncSelectFilterField',
    'createUserFilterField',
    'createSupplierFilterField',
    'createProductFilterField',
    'createEmployeeLookupFilterField',
    'createPurchaseOrderFilterField',
    'createGoodsReceiptFilterField',
    'createAssetFilterField',
    'createAssetCategoryFilterField',
    'createAssetStocktakeFilterField',

    'createSupplierWarehouseProductFilterFields',
    'createPurchasingReportScopeFilterFields',
    'createGoodsReceiptStatusFilterField',
    'createPurchaseOrderStatusFilterField',
    'createPurchaseOrderStatusCategoryFilterField',
    'createBinaryStatusFilterField',
    'createFiscalYearStatusFilterField',
    'createCoaVersionStatusFilterField',
    'createJournalEntryStatusFilterField',
    'createApprovalAuditEventFilterField',
    'createSecurityLogEventFilterField',
    'createPipelineFilterField',
    'createApprovalDelegationUserFilterFields',
    'createSupplierWarehouseFilterFields',
    'createPurchaseRequestContextFilterFields',
    'createProductWarehouseFilterFields',
    'createWarehouseStatusFilterFields',
    'createAssetCategoryBranchFilterFields',
    'createAssetReportScopeFilterFields',
    'createAssetStocktakeVarianceScopeFilterFields',

    'createInventoryStocktakeFilterSelectFields',
    'createAssetSupplierFilterFields',
    'createMaintenanceTypeStatusFilterFields',
    'createAssetStatusConditionFilterFields',
    'createWarehouseBranchFilterFields',
    'createProductWarehouseBranchCategoryFilterFields',
    'createInventoryReportScopeFilterFields',
    'createCustomerFinancialFilterFields',
    'createInventoryStocktakeReportFilterField',
    'createInventoryStocktakeVarianceResultFilterField',
    'createSelectFilterFields',
    'createDateFilterFields',
    'createDateRangeFilterFields',
    'createCostRangeFilterFields',
]);

function helperFunctionSource(source) {
    const blocks = [];
    const header = /^export (?:function|const) (\w+)/gm;
    const starts = [...source.matchAll(header)];
    starts.forEach((m, i) => {
        const end = i + 1 < starts.length ? starts[i + 1].index : source.length;
        blocks.push({ name: m[1], start: m.index, body: source.slice(m.index, end) });
    });
    return blocks;
}

function scanFile(file, rel, source, skipFunction) {
    for (const pattern of literalPatterns) {
        for (const match of source.matchAll(pattern)) {
            const text = match[1];
            if (!englishLiteral.test(text) || isoCurrencyCode.test(text)) {
                continue;
            }
            if (skipFunction && skipFunction(match.index)) {
                continue;
            }
            const line = source.slice(0, match.index).split('\n').length;
            violations.push(
                `resources/js/components/${rel}:${line}  literal '${text}' harus lewat { key, fallback } (lang/en.json + id.json)`,
            );
        }
    }
}

for (const file of walk(componentsDir)) {
    const rel = relative(componentsDir, file).split('\\').join('/');
    if (pendingFiles.has(rel)) {
        continue;
    }
    scanned.push(rel);
    scanFile(file, rel, readFileSync(file, 'utf8'), null);
}

{
    const rel = relative(componentsDir, sharedHelper).split('\\').join('/');
    const source = readFileSync(sharedHelper, 'utf8');
    const blocks = helperFunctionSource(source);
    scanned.push(rel);
    scanFile(sharedHelper, rel, source, (index) => {
        const block = blocks.findLast((b) => b.start <= index);
        return block !== undefined && pendingHelperFunctions.has(block.name);
    });
}

if (violations.length > 0) {
    console.error(violations.join('\n'));
    console.error(
        `\n${violations.length} label/placeholder filter literal English. Ganti ke { key, fallback } dan tambahkan kunci di lang/en.json + id.json.`,
    );
    process.exit(1);
}

console.log(
    `Filter label check passed: ${scanned.length} filter file(s) scanned, ${pendingFiles.size} pending (allowlist).`,
);
