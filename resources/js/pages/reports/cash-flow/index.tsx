import type { FinancialReportFiscalYear } from '@/components/reports/financial/FinancialReportPageShell';
import {
    FinancialTableCard,
    SingleYearFinancialReportPageShell,
    useSingleYearReportSearchParams,
    type FinancialTableRow,
} from '@/components/reports/financial/FinancialTableReportPage';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency } from '@/lib/utils';

import axios from '@/lib/axios';
import { useQuery } from '@tanstack/react-query';

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

export default function CashFlow() {
    const { urlYearId, handleYearChange } = useSingleYearReportSearchParams();

    const { data, isLoading, error } = useQuery<CashFlowResponse>({
        queryKey: ['cash-flow', urlYearId],
        queryFn: async () => {
            const params = new URLSearchParams();
            if (urlYearId) params.append('fiscal_year_id', urlYearId);
            const response = await axios.get(
                `/api/reports/cash-flow?${params.toString()}`,
            );
            return response.data;
        },
    });

    const fiscalYears = Array.isArray(data?.fiscalYears)
        ? data.fiscalYears
        : [];
    const selectedYearId = data?.selectedYearId || 0;
    const report = Array.isArray(data?.report) ? data.report : [];

    const totalInflow = report.reduce(
        (sum, item) => sum + (item.inflow || 0),
        0,
    );
    const totalOutflow = report.reduce(
        (sum, item) => sum + (item.outflow || 0),
        0,
    );
    const netCashFlow = totalInflow - totalOutflow;

    const selectedFiscalYear = fiscalYears.find(
        (fy) => fy.id === selectedYearId,
    );

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
                selectedFiscalYear ? (
                    <div className="text-sm text-muted-foreground">
                        {selectedFiscalYear.name} • {selectedFiscalYear.status}
                    </div>
                ) : undefined
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
        </SingleYearFinancialReportPageShell>
    );
}
