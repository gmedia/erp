import { ComputedSectionsCard } from '@/components/reports/financial/ComputedSectionsCard';
import { FinancialReportExportButton } from '@/components/reports/financial/FinancialReportExportButton';
import {
    FinancialReportHeaderMeta,
    type FinancialReportFiscalYear,
} from '@/components/reports/financial/FinancialReportPageShell';
import { FinancialStatusBadge } from '@/components/reports/financial/FinancialSummaryCard';
import {
    SingleYearFinancialReportPageShell,
    useSingleYearFinancialReportPage,
    type FinancialTableRow,
} from '@/components/reports/financial/FinancialTableReportPage';
import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useExport } from '@/hooks/useExport';
import { formatCurrency } from '@/lib/utils';

interface AccountItem extends FinancialTableRow {
    id: number;
    code: string;
    name: string;
    type: string;
    level: number;
    parent_id: number | null;
    normal_balance: 'debit' | 'credit';
    debit: number;
    credit: number;
}

interface TrialBalanceResponse {
    fiscalYears: FinancialReportFiscalYear[];
    selectedYearId: number;
    report: AccountItem[];
}

const emptyTrialBalanceReport: TrialBalanceResponse['report'] = [];

export default function TrialBalance() {
    const {
        fiscalYears,
        selectedYearId,
        branchId,
        report,
        computedSections,
        selectedFiscalYear,
        handleYearChange,
        handleBranchChange,
        isLoading,
        error,
    } = useSingleYearFinancialReportPage<TrialBalanceResponse['report']>({
        queryKey: 'trial-balance',
        endpoint: 'trial-balance',
        emptyReport: emptyTrialBalanceReport,
    });

    const { exporting, exportData } = useExport({
        endpoint: '/api/reports/trial-balance/export',
    });

    const totalDebit = report.reduce((sum, item) => sum + (item.debit || 0), 0);
    const totalCredit = report.reduce(
        (sum, item) => sum + (item.credit || 0),
        0,
    );
    const difference = Math.abs(totalDebit - totalCredit);
    const isBalanced = Math.abs(totalDebit - totalCredit) < 0.01;

    return (
        <SingleYearFinancialReportPageShell
            title="Trial Balance"
            path="/reports/trial-balance"
            fiscalYears={fiscalYears}
            selectedYearId={selectedYearId}
            onYearChange={handleYearChange}
            branchId={branchId}
            onBranchChange={handleBranchChange}
            isLoading={isLoading}
            hasError={!!error}
            headerMeta={
                <FinancialReportHeaderMeta fiscalYear={selectedFiscalYear}>
                    {report.length > 0 && (
                        <FinancialStatusBadge
                            isPositive={isBalanced}
                            positiveLabel="Balanced"
                            negativeLabel={`Unbalanced • ${formatCurrency(difference)}`}
                        />
                    )}
                </FinancialReportHeaderMeta>
            }
            headerActions={
                <FinancialReportExportButton
                    exporting={exporting}
                    selectedYearId={selectedYearId}
                    branchId={branchId}
                    onExport={exportData}
                />
            }
        >
            <div className="flex flex-col gap-3">
                <div className="overflow-hidden rounded-md border">
                    <div className="overflow-x-auto">
                        <Table className="md:min-w-[760px]">
                            <TableHeader className="[&_th]:text-muted-foreground">
                                <TableRow>
                                    <TableHead className="w-[64px] md:w-[120px]">
                                        Code
                                    </TableHead>
                                    <TableHead className="max-w-[104px] md:max-w-none">
                                        Account Name
                                    </TableHead>
                                    <TableHead className="hidden w-[120px] md:table-cell">
                                        Type
                                    </TableHead>
                                    <TableHead className="text-right text-xs whitespace-nowrap tabular-nums md:text-sm">
                                        Debit
                                    </TableHead>
                                    <TableHead className="text-right text-xs whitespace-nowrap tabular-nums md:text-sm">
                                        Credit
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {report.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="h-24 text-center text-muted-foreground"
                                        >
                                            No data available for the selected
                                            fiscal year.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    report.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="font-mono text-xs text-muted-foreground">
                                                {item.code}
                                            </TableCell>
                                            <TableCell className="max-w-[104px] md:max-w-none">
                                                <div
                                                    className="truncate"
                                                    style={{
                                                        paddingLeft: `${Math.max(0, item.level - 1) * 1.25}rem`,
                                                    }}
                                                >
                                                    {item.name}
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden text-muted-foreground capitalize md:table-cell">
                                                {item.type}
                                            </TableCell>
                                            <TableCell className="text-right text-xs whitespace-nowrap tabular-nums md:text-sm">
                                                {item.debit === 0
                                                    ? '-'
                                                    : formatCurrency(
                                                          item.debit,
                                                      )}
                                            </TableCell>
                                            <TableCell className="text-right text-xs whitespace-nowrap tabular-nums md:text-sm">
                                                {item.credit === 0
                                                    ? '-'
                                                    : formatCurrency(
                                                          item.credit,
                                                      )}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                            {report.length > 0 && (
                                <TableFooter>
                                    <TableRow>
                                        <TableCell
                                            colSpan={3}
                                            className="text-right font-semibold"
                                        >
                                            Total
                                        </TableCell>
                                        <TableCell
                                            className={`text-right font-semibold tabular-nums ${isBalanced ? '' : 'text-destructive'}`}
                                        >
                                            {formatCurrency(totalDebit)}
                                        </TableCell>
                                        <TableCell
                                            className={`text-right font-semibold tabular-nums ${isBalanced ? '' : 'text-destructive'}`}
                                        >
                                            {formatCurrency(totalCredit)}
                                        </TableCell>
                                    </TableRow>
                                </TableFooter>
                            )}
                        </Table>
                    </div>
                </div>
                {!isBalanced && report.length > 0 && (
                    <div className="rounded-md border border-destructive/30 bg-destructive/5 px-3 py-2 text-sm text-destructive">
                        Trial balance is not balanced. Difference:{' '}
                        {formatCurrency(difference)}
                    </div>
                )}
            </div>

            <ComputedSectionsCard
                sections={computedSections}
                title="Trial Balance Summary"
            />
        </SingleYearFinancialReportPageShell>
    );
}
