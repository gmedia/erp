import {
    ComputedSectionsCard,
    type ComputedSection,
} from '@/components/reports/financial/ComputedSectionsCard';
import { FinancialReportExportButton } from '@/components/reports/financial/FinancialReportExportButton';
import {
    FinancialReportHeaderMeta,
    type FinancialReportFiscalYear,
} from '@/components/reports/financial/FinancialReportPageShell';
import { getSignedAmountTextClass } from '@/components/reports/financial/FinancialReportSection';
import {
    FinancialTableCard,
    SingleYearFinancialReportPageShell,
    useSingleYearFinancialReportPage,
    type FinancialTableRow,
} from '@/components/reports/financial/FinancialTableReportPage';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/contexts/i18n-context';
import { useExport } from '@/hooks/useExport';
import { cn, formatCurrency } from '@/lib/utils';

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

function ActivitySectionsCard({
    title,
    sections,
    netChange,
    netChangeLabel,
}: Readonly<{
    title: string;
    sections: ComputedSection[];
    netChange: number;
    netChangeLabel: string;
}>) {
    const { t } = useTranslation();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
                {sections.map((section) => {
                    const key = `reports.sections.${section.code}`;
                    const translated = t(key);
                    const label =
                        translated === key ? section.name : translated;

                    return (
                        <div
                            key={section.code}
                            className="flex items-center justify-between gap-4 py-1"
                        >
                            <span className="text-sm text-muted-foreground">
                                {label}
                            </span>
                            <span
                                className={cn(
                                    'text-sm font-semibold whitespace-nowrap tabular-nums',
                                    getSignedAmountTextClass(section.value),
                                )}
                            >
                                {formatCurrency(section.value)}
                            </span>
                        </div>
                    );
                })}
                <div className="flex items-center justify-between gap-4 border-t-2 border-foreground/20 pt-2">
                    <span className="text-sm font-bold">{netChangeLabel}</span>
                    <span
                        className={cn(
                            'text-sm font-bold whitespace-nowrap tabular-nums',
                            getSignedAmountTextClass(netChange),
                        )}
                    >
                        {formatCurrency(netChange)}
                    </span>
                </div>
            </CardContent>
        </Card>
    );
}

function CashFlowSummaryCards({
    openingCash,
    netChange,
    closingCash,
}: Readonly<{
    openingCash: number;
    netChange: number;
    closingCash: number;
}>) {
    const { t } = useTranslation();
    const items = [
        {
            label: t('reports.sections.opening_cash_balance'),
            value: openingCash,
        },
        { label: t('reports.sections.net_change'), value: netChange },
        {
            label: t('reports.sections.closing_cash_balance'),
            value: closingCash,
        },
    ];

    return (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
            {items.map((item) => (
                <Card key={item.label}>
                    <CardHeader>
                        <CardTitle>{item.label}</CardTitle>
                    </CardHeader>
                    <CardContent
                        className={cn(
                            'text-2xl font-semibold whitespace-nowrap tabular-nums',
                            getSignedAmountTextClass(item.value),
                        )}
                    >
                        {formatCurrency(item.value)}
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}

function CashFlowActivityGrid({
    activitySections,
    otherSections,
    netChange,
}: Readonly<{
    activitySections: ComputedSection[];
    otherSections: ComputedSection[];
    netChange: number;
}>) {
    const { t } = useTranslation();

    return (
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <ActivitySectionsCard
                title={t('reports.cashFlowActivities.title')}
                sections={activitySections}
                netChange={netChange}
                netChangeLabel={t('reports.sections.net_change')}
            />
            <ComputedSectionsCard
                sections={otherSections}
                title={t('reports.cashFlowActivities.summary')}
            />
        </div>
    );
}

function CashFlowDetailTable({
    items,
    totalInflow,
    totalOutflow,
}: Readonly<{
    items: CashFlowItem[];
    totalInflow: number;
    totalOutflow: number;
}>) {
    const { t } = useTranslation();

    return (
        <FinancialTableCard
            title={t('reports.cashFlowActivities.detail')}
            items={items}
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
    );
}

const EXCLUDED_SUMMARY_SECTION_CODES = [
    'operating_header',
    'investing_header',
    'financing_header',
    'operating_cash_flow',
    'investing_cash_flow',
    'financing_cash_flow',
] as const;

export default function CashFlow() {
    const {
        fiscalYears,
        selectedYearId,
        branchId,
        report,
        computedSections,
        rawData,
        selectedFiscalYear,
        handleYearChange,
        handleBranchChange,
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

    const openingCash = rawData?.opening_cash_balance ?? 0;
    const closingCash =
        rawData?.closing_cash_balance ?? openingCash + netCashFlow;

    // Angka aktivitas langsung dari service (root `activities`), tanpa bergantung
    // pada konfigurasi laporan yang sudah diseed ulang.
    const activities = rawData?.activities ?? {
        operating: 0,
        investing: 0,
        financing: 0,
    };
    const activitySections: ComputedSection[] = [
        {
            code: 'operating_cash_flow',
            name: 'Cash Flows from Operating Activities',
            section_type: 'subtotal',
            value: activities.operating,
            formula: null,
            sort_order: 1,
        },
        {
            code: 'investing_cash_flow',
            name: 'Cash Flows from Investing Activities',
            section_type: 'subtotal',
            value: activities.investing,
            formula: null,
            sort_order: 2,
        },
        {
            code: 'financing_cash_flow',
            name: 'Cash Flows from Financing Activities',
            section_type: 'subtotal',
            value: activities.financing,
            formula: null,
            sort_order: 3,
        },
    ];
    const otherSections = computedSections.filter(
        (section) =>
            !(EXCLUDED_SUMMARY_SECTION_CODES as readonly string[]).includes(
                section.code,
            ),
    );

    return (
        <SingleYearFinancialReportPageShell
            title="Cash Flow"
            descriptionKey="reports.pageDescriptions.cash-flow"
            path="/reports/cash-flow"
            fiscalYears={fiscalYears}
            selectedYearId={selectedYearId}
            onYearChange={handleYearChange}
            branchId={branchId}
            onBranchChange={handleBranchChange}
            isLoading={isLoading}
            hasError={!!error}
            headerMeta={
                <FinancialReportHeaderMeta fiscalYear={selectedFiscalYear} />
            }
            headerActions={
                <FinancialReportExportButton
                    exporting={exporting}
                    selectedYearId={selectedYearId}
                    branchId={branchId}
                    onExport={exportData}
                />
            }
            preContent={
                <CashFlowSummaryCards
                    openingCash={openingCash}
                    netChange={netCashFlow}
                    closingCash={closingCash}
                />
            }
        >
            <CashFlowActivityGrid
                activitySections={activitySections}
                otherSections={otherSections}
                netChange={netCashFlow}
            />

            <CashFlowDetailTable
                items={report}
                totalInflow={totalInflow}
                totalOutflow={totalOutflow}
            />
        </SingleYearFinancialReportPageShell>
    );
}
