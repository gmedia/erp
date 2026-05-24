import { ComputedSectionsCard } from '@/components/reports/financial/ComputedSectionsCard';
import {
    FinancialReportHeaderMeta,
    type FinancialReportFiscalYear,
} from '@/components/reports/financial/FinancialReportPageShell';
import {
    FinancialTableCard,
    SingleYearFinancialReportPageShell,
    useSingleYearFinancialReportPage,
    type FinancialTableRow,
} from '@/components/reports/financial/FinancialTableReportPage';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useExport } from '@/hooks/useExport';
import { formatCurrency } from '@/lib/utils';
import { Download, Loader2 } from 'lucide-react';

interface CashFlowItem extends FinancialTableRow {
    id: number;
    code: string;
    name: string;
    type: string;
    normal_balance: 'debit' | 'credit';
    level: number;
    parent_id: number | null;
    inflow: number;
    outflow: number;
}

interface CashFlowResponse {
    fiscalYears: FinancialReportFiscalYear[];
    selectedYearId: number;
    report: CashFlowItem[];
}

const emptyCashFlowReport: CashFlowResponse['report'] = [];

export default function CashFlow() {
    const {
        fiscalYears,
        selectedYearId,
        report,
        computedSections,
        selectedFiscalYear,
        handleYearChange,
        isLoading,
        error,
    } = useSingleYearFinancialReportPage<CashFlowResponse['report']>({
        queryKey: 'cash-flow',
        endpoint: 'cash-flow',
        emptyReport: emptyCashFlowReport,
    });

    const { exporting, exportData } = useExport({
        endpoint: '/api/reports/cash-flow/export',
    });

    const totalInflow = report.reduce(
        (sum, item) => sum + (item.inflow || 0),
        0,
    );
    const totalOutflow = report.reduce(
        (sum, item) => sum + (item.outflow || 0),
        0,
    );
    const netCashFlow = totalInflow - totalOutflow;

    return (
        <SingleYearFinancialReportPageShell
            title="Cash Flow"
            path="/reports/cash-flow"
            fiscalYears={fiscalYears}
            selectedYearId={selectedYearId}
            onYearChange={handleYearChange}
            isLoading={isLoading}
            hasError={!!error}
            headerMeta={
                <FinancialReportHeaderMeta fiscalYear={selectedFiscalYear} />
            }
            headerActions={
                <Button
                    variant="outline"
                    size="sm"
                    disabled={!selectedYearId || exporting}
                    onClick={() =>
                        exportData({
                            fiscal_year_id: String(selectedYearId),
                        })
                    }
                >
                    {exporting ? (
                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    ) : (
                        <Download className="mr-2 h-4 w-4" />
                    )}
                    {exporting ? 'Exporting...' : 'Export'}
                </Button>
            }
            preContent={
                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Total Inflow</CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold tabular-nums">
                            {formatCurrency(totalInflow)}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Total Outflow</CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold tabular-nums">
                            {formatCurrency(totalOutflow)}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Net Cash Flow</CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold tabular-nums">
                            {formatCurrency(netCashFlow)}
                        </CardContent>
                    </Card>
                </div>
            }
        >
            <FinancialTableCard
                title="Cash Flow Report"
                items={report}
                amountColumns={[
                    {
                        header: 'Inflow',
                        total: totalInflow,
                        value: (item) => item.inflow,
                    },
                    {
                        header: 'Outflow',
                        total: totalOutflow,
                        value: (item) => item.outflow,
                    },
                ]}
                emptyMessage="No data available for the selected fiscal year."
                scrollAreaClassName="max-h-[calc(100vh-22rem)]"
            />

            <ComputedSectionsCard
                sections={computedSections}
                title="Cash Flow Summary"
            />
        </SingleYearFinancialReportPageShell>
    );
}
