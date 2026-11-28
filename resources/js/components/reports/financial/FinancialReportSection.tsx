import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/contexts/i18n-context';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn, formatCurrency } from '@/lib/utils';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import { translateOr } from './FinancialReportPageShell';

export interface ReportAccountNode {
    id: number | string;
    code: string;
    name: string;
    balance: number;
    comparison_balance?: number;
    change?: number;
    change_percentage?: number;
    children?: ReportAccountNode[];
    level: number;
}

export type FinancialReportMetric =
    | 'assets'
    | 'liabilities'
    | 'equity'
    | 'revenues'
    | 'expenses'
    | 'revenue'
    | 'expense';

type FinancialReportTotals = Partial<
    Record<FinancialReportMetric, number> &
        Record<`comparison_${FinancialReportMetric}`, number> &
        Record<`change_${FinancialReportMetric}`, number> &
        Record<`change_percentage_${FinancialReportMetric}`, number>
>;

type FinancialReportSectionConfig = {
    title: string;
    titleKey: string;
    metric: FinancialReportMetric;
};

type FinancialReportSectionGroupReport = Partial<
    Record<FinancialReportMetric, ReportAccountNode[]>
>;

export const financialPositionSectionConfigs: readonly FinancialReportSectionConfig[] =
    [
        {
            title: 'Assets',
            titleKey: 'reports.financial.section.assets',
            metric: 'assets',
        },
        {
            title: 'Liabilities',
            titleKey: 'reports.financial.section.liabilities',
            metric: 'liabilities',
        },
        {
            title: 'Equity',
            titleKey: 'reports.financial.section.equity',
            metric: 'equity',
        },
    ];

export const getChangeTextClass = (
    value: number,
    metric: FinancialReportMetric = 'assets',
): string => {
    if (value < 0) {
        return 'text-destructive-foreground';
    }

    if (value > 0) {
        return metric === 'liabilities' || metric === 'equity'
            ? 'text-foreground'
            : 'text-success';
    }

    return 'text-muted-foreground';
};

export const getSignedAmountTextClass = (value: number): string => {
    if (value < 0) {
        return 'text-destructive-foreground';
    }

    return '';
};

/**
 * Format persentase perubahan; pembanding 0 menghasilkan `—` (n/a) alih-alih
 * `0.0%` yang menyesatkan (W4-21).
 */
export const getChangePercentageTextClass = (
    percentage: number | null | undefined,
    comparisonBase: number | null | undefined,
): string => {
    if (
        comparisonBase === null ||
        comparisonBase === undefined ||
        comparisonBase === 0
    ) {
        return 'text-muted-foreground';
    }

    return getChangeTextClass(percentage ?? 0);
};

export const formatChangePercentage = (
    percentage: number | null | undefined,
    comparisonBase: number | null | undefined,
): string => {
    if (
        comparisonBase === null ||
        comparisonBase === undefined ||
        comparisonBase === 0
    ) {
        return '—';
    }

    return `${(percentage ?? 0).toFixed(1)}%`;
};

function AccountRow({
    node,
    isExpanded = true,
    showComparison = false,
    metric = 'assets',
    compactIndent = false,
}: Readonly<{
    node: ReportAccountNode;
    isExpanded?: boolean;
    showComparison?: boolean;
    metric?: FinancialReportMetric;
    compactIndent?: boolean;
}>) {
    const [expanded, setExpanded] = useState(isExpanded);
    const isMobile = useIsMobile();
    const hasChildren = node.children && node.children.length > 0;
    const changeValue = node.change || 0;
    const changeTextClass = getChangeTextClass(changeValue, metric);
    const expandIcon = expanded ? (
        <ChevronDown className="h-4 w-4 text-muted-foreground" />
    ) : (
        <ChevronRight className="h-4 w-4 text-muted-foreground" />
    );

    return (
        <div className="flex flex-col">
            <div
                className={cn(
                    'flex w-full items-center gap-2 border-b border-border/40 px-2 py-2 text-sm hover:bg-muted/40 md:w-auto md:min-w-max',
                    hasChildren && 'bg-muted/20 font-semibold',
                )}
            >
                <button
                    type="button"
                    className="flex min-w-[10rem] flex-1 cursor-pointer items-start gap-2 text-left md:items-center"
                    onClick={() => hasChildren && setExpanded(!expanded)}
                    style={{
                        paddingLeft: `${(node.level - 1) * (isMobile && compactIndent ? 0.375 : 1.5)}rem`,
                    }}
                >
                    {hasChildren ? expandIcon : <div className="w-4" />}
                    <span className="font-mono text-xs whitespace-nowrap text-muted-foreground">
                        {node.code}
                    </span>
                    <span className="min-w-0 break-words md:truncate">
                        {node.name}
                    </span>
                </button>
                <div className="flex shrink-0 gap-2 text-right tabular-nums sm:gap-4">
                    <div
                        className={cn(
                            'w-28 shrink-0 md:w-36',
                            getSignedAmountTextClass(node.balance),
                        )}
                    >
                        {formatCurrency(node.balance)}
                    </div>
                    {showComparison && (
                        <>
                            <div
                                className={cn(
                                    'w-28 shrink-0 text-muted-foreground md:w-36',
                                    getSignedAmountTextClass(
                                        node.comparison_balance || 0,
                                    ),
                                )}
                            >
                                {formatCurrency(node.comparison_balance || 0)}
                            </div>
                            <div
                                className={cn(
                                    'w-24 shrink-0 md:w-28',
                                    changeTextClass,
                                )}
                            >
                                {formatCurrency(changeValue)}
                            </div>
                            <div
                                className={cn(
                                    'w-14 shrink-0 md:w-16',
                                    getChangePercentageTextClass(
                                        node.change_percentage,
                                        node.comparison_balance,
                                    ),
                                )}
                            >
                                {formatChangePercentage(
                                    node.change_percentage,
                                    node.comparison_balance,
                                )}
                            </div>
                        </>
                    )}
                </div>
            </div>
            {hasChildren && expanded && (
                <div>
                    {node.children!.map((child) => (
                        <AccountRow
                            key={child.id}
                            node={child}
                            showComparison={showComparison}
                            metric={metric}
                            compactIndent={compactIndent}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

export function FinancialReportSection({
    title,
    titleKey,
    nodes,
    total,
    comparisonTotal,
    change,
    changePercentage,
    showComparison,
    metric = 'assets',
    compactIndent = false,
}: Readonly<{
    title: string;
    titleKey?: string;
    nodes: ReportAccountNode[];
    total: number;
    comparisonTotal?: number;
    change?: number;
    changePercentage?: number;
    showComparison?: boolean;
    metric?: FinancialReportMetric;
    compactIndent?: boolean;
}>) {
    const { t } = useTranslation();
    const [expandAll, setExpandAll] = useState(true);
    const [expandKey, setExpandKey] = useState(0);
    const changeValue = change || 0;
    const changeTextClass = getChangeTextClass(changeValue, metric);
    const sectionTitle = titleKey ? translateOr(t, titleKey, title) : title;

    const setExpanded = (value: Readonly<boolean>) => {
        setExpandAll(value);
        setExpandKey((k) => k + 1);
    };

    return (
        <Card className="mb-6 min-w-0">
            <CardHeader className="pb-2">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-wrap items-center gap-2">
                        <CardTitle className="text-lg">{sectionTitle}</CardTitle>
                        <div className="flex items-center gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setExpanded(true)}
                                disabled={expandAll}
                            >
                                {t('reports.financial.expand_all')}
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setExpanded(false)}
                                disabled={!expandAll}
                            >
                                {t('reports.financial.collapse_all')}
                            </Button>
                        </div>
                    </div>
                    <div className="flex w-full flex-wrap justify-between gap-2 text-right whitespace-nowrap tabular-nums sm:w-auto sm:flex-nowrap sm:justify-end sm:gap-4">
                        <span
                            className={cn(
                                'w-28 text-lg font-bold md:w-36',
                                getSignedAmountTextClass(total),
                            )}
                        >
                            {formatCurrency(total)}
                        </span>
                        {showComparison && (
                            <>
                                <span
                                    className={cn(
                                        'w-28 text-lg font-bold text-muted-foreground md:w-36',
                                        getSignedAmountTextClass(
                                            comparisonTotal || 0,
                                        ),
                                    )}
                                >
                                    {formatCurrency(comparisonTotal || 0)}
                                </span>
                                <span
                                    className={cn(
                                        'w-24 text-lg font-bold md:w-28',
                                        changeTextClass,
                                    )}
                                >
                                    {formatCurrency(changeValue)}
                                </span>
                                <span
                                    className={cn(
                                        'w-14 text-lg font-bold md:w-16',
                                        getChangePercentageTextClass(
                                            changePercentage,
                                            comparisonTotal,
                                        ),
                                    )}
                                >
                                    {formatChangePercentage(
                                        changePercentage,
                                        comparisonTotal,
                                    )}
                                </span>
                            </>
                        )}
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                {nodes.length === 0 ? (
                    <div className="py-4 text-center text-muted-foreground italic">
                        {t('reports.financial.no_accounts')}
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-md border">
                        <div className="max-h-[60vh] w-full min-w-0 overflow-auto">
                            <div
                                className={cn(
                                    'sticky top-0 z-10 flex w-full items-center border-b bg-background px-2 py-2 text-xs font-medium text-muted-foreground md:w-auto md:min-w-max',
                                )}
                            >
                                {/* Spacer selebar ikon expand agar label
                                    sejajar dengan teks baris (W5-12). */}
                                <div className="flex min-w-[10rem] flex-1 items-center gap-2 md:min-w-40">
                                    <div className="w-4" aria-hidden />
                                    <span>
                                        {t('reports.financial.column.account')}
                                    </span>
                                </div>
                                <div className="flex shrink-0 gap-2 text-right tabular-nums sm:gap-4">
                                    <div className="w-28 shrink-0 md:w-36">
                                        {t('reports.financial.column.current')}
                                    </div>
                                    {showComparison && (
                                        <>
                                            <div className="w-28 shrink-0 md:w-36">
                                                {t(
                                                    'reports.financial.column.comparison',
                                                )}
                                            </div>
                                            <div className="w-24 shrink-0 md:w-28">
                                                {t(
                                                    'reports.financial.column.change',
                                                )}
                                            </div>
                                            <div className="w-14 shrink-0 md:w-16">
                                                {t(
                                                    'reports.financial.column.percentage',
                                                )}
                                            </div>
                                        </>
                                    )}
                                </div>
                            </div>
                            {nodes.map((node) => (
                                <AccountRow
                                    key={`${expandKey}-${node.id}`}
                                    node={node}
                                    showComparison={showComparison}
                                    metric={metric}
                                    compactIndent={compactIndent}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export function ComparisonFinancialReportSection({
    title,
    titleKey,
    metric,
    nodes,
    totals,
    showComparison = false,
    compactIndent = false,
}: Readonly<{
    title: string;
    titleKey?: string;
    metric: FinancialReportMetric;
    nodes: ReportAccountNode[];
    totals: FinancialReportTotals;
    showComparison?: boolean;
    compactIndent?: boolean;
}>) {
    return (
        <FinancialReportSection
            title={title}
            titleKey={titleKey}
            nodes={nodes}
            total={totals[metric] || 0}
            comparisonTotal={totals[`comparison_${metric}`]}
            change={totals[`change_${metric}`]}
            changePercentage={totals[`change_percentage_${metric}`]}
            showComparison={showComparison}
            metric={metric}
            compactIndent={compactIndent}
        />
    );
}

export function ComparisonFinancialReportSectionGroup({
    sections,
    report,
    totals,
    showComparison = false,
    className,
    compactIndent = false,
}: Readonly<{
    sections: readonly FinancialReportSectionConfig[];
    report: FinancialReportSectionGroupReport;
    totals: FinancialReportTotals;
    showComparison?: boolean;
    className?: string;
    compactIndent?: boolean;
}>) {
    const content = sections.map(({ title, titleKey, metric }) => (
        <ComparisonFinancialReportSection
            key={metric}
            title={title}
            titleKey={titleKey}
            metric={metric}
            nodes={report[metric] || []}
            totals={totals}
            showComparison={showComparison}
            compactIndent={compactIndent}
        />
    ));

    if (!className) {
        return <>{content}</>;
    }

    return <div className={cn('min-w-0', className)}>{content}</div>;
}
