import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn, formatCurrency } from '@/lib/utils';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { useState } from 'react';

interface FiscalYear {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    status: string;
}

interface AccountNode {
    id: number | string;
    code: string;
    name: string;
    balance: number;
    comparison_balance?: number;
    change?: number;
    change_percentage?: number;
    children?: AccountNode[];
    level: number;
}

interface Props {
    fiscalYears: FiscalYear[];
    selectedYearId: number;
    comparisonYearId?: number;
    report: {
        revenues: AccountNode[];
        expenses: AccountNode[];
        totals: {
            revenue: number;
            expense: number;
            net_income: number;
            comparison_revenue?: number;
            comparison_expense?: number;
            comparison_net_income?: number;
            change_revenue?: number;
            change_percentage_revenue?: number;
            change_expense?: number;
            change_percentage_expense?: number;
            change_net_income?: number;
            change_percentage_net_income?: number;
        };
    };
}

const AccountRow = ({
    node,
    isExpanded = true,
    showComparison = false,
}: {
    node: AccountNode;
    isExpanded?: boolean;
    showComparison?: boolean;
}) => {
    const [expanded, setExpanded] = useState(isExpanded);
    const hasChildren = node.children && node.children.length > 0;
    const changeValue = node.change || 0;

    return (
        <div className="flex flex-col">
            <div
                className={cn(
                    'flex items-center gap-2 py-2 px-2 text-sm border-b border-border/40 hover:bg-muted/40',
                    hasChildren && 'bg-muted/20 font-semibold',
                )}
            >
                <div
                    className="flex items-center flex-1 gap-2 cursor-pointer"
                    onClick={() => hasChildren && setExpanded(!expanded)}
                    style={{ paddingLeft: `${(node.level - 1) * 1.5}rem` }}
                >
                    {hasChildren ? (
                        expanded ? (
                            <ChevronDown className="h-4 w-4 text-muted-foreground" />
                        ) : (
                            <ChevronRight className="h-4 w-4 text-muted-foreground" />
                        )
                    ) : (
                        <div className="w-4" />
                    )}
                    <span className="font-mono text-muted-foreground text-xs">{node.code}</span>
                    <span className="truncate">{node.name}</span>
                </div>
                <div className="flex gap-4 text-right tabular-nums">
                    <div className="w-32 font-mono">{formatCurrency(node.balance)}</div>
                    {showComparison && (
                        <>
                            <div className="w-32 font-mono text-muted-foreground">
                                {formatCurrency(node.comparison_balance || 0)}
                            </div>
                            <div
                                className={cn(
                                    'w-28 font-mono',
                                    changeValue < 0
                                        ? 'text-red-500'
                                        : changeValue > 0
                                          ? 'text-green-600'
                                          : 'text-muted-foreground',
                                )}
                            >
                                {formatCurrency(changeValue)}
                            </div>
                            <div
                                className={cn(
                                    'w-16 font-mono',
                                    changeValue < 0
                                        ? 'text-red-500'
                                        : changeValue > 0
                                          ? 'text-green-600'
                                          : 'text-muted-foreground',
                                )}
                            >
                                {(node.change_percentage || 0).toFixed(1)}%
                            </div>
                        </>
                    )}
                </div>
            </div>
            {hasChildren && expanded && (
                <div>
                    {node.children!.map((child) => (
                        <AccountRow key={child.id} node={child} showComparison={showComparison} />
                    ))}
                </div>
            )}
        </div>
    );
};

function Section({
    title,
    nodes,
    total,
    comparisonTotal,
    change,
    changePercentage,
    showComparison,
}: {
    title: string;
    nodes: AccountNode[];
    total: number;
    comparisonTotal?: number;
    change?: number;
    changePercentage?: number;
    showComparison?: boolean;
}) {
    const [expandAll, setExpandAll] = useState(true);
    const [expandKey, setExpandKey] = useState(0);
    const changeValue = change || 0;

    const setExpanded = (value: boolean) => {
        setExpandAll(value);
        setExpandKey((k) => k + 1);
    };

    return (
        <Card className="mb-6">
            <CardHeader className="pb-2">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-wrap items-center gap-2">
                        <CardTitle className="text-lg">{title}</CardTitle>
                        <div className="flex items-center gap-2">
                            <Button size="sm" variant="outline" onClick={() => setExpanded(true)} disabled={expandAll}>
                                Expand all
                            </Button>
                            <Button size="sm" variant="outline" onClick={() => setExpanded(false)} disabled={!expandAll}>
                                Collapse all
                            </Button>
                        </div>
                    </div>
                    <div className="flex gap-4 text-right tabular-nums">
                        <span className="w-32 text-lg font-bold">{formatCurrency(total)}</span>
                        {showComparison && (
                            <>
                                <span className="w-32 text-lg font-bold text-muted-foreground">
                                    {formatCurrency(comparisonTotal || 0)}
                                </span>
                                <span
                                    className={cn(
                                        'w-28 text-lg font-bold',
                                        changeValue < 0 ? 'text-red-500' : changeValue > 0 ? 'text-green-600' : 'text-muted-foreground',
                                    )}
                                >
                                    {formatCurrency(changeValue)}
                                </span>
                                <span
                                    className={cn(
                                        'w-16 text-lg font-bold',
                                        changeValue < 0 ? 'text-red-500' : changeValue > 0 ? 'text-green-600' : 'text-muted-foreground',
                                    )}
                                >
                                    {(changePercentage || 0).toFixed(1)}%
                                </span>
                            </>
                        )}
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                {nodes.length === 0 ? (
                    <div className="py-4 text-center text-muted-foreground italic">No accounts found</div>
                ) : (
                    <div className="rounded-md border overflow-hidden">
                        <div className="max-h-[60vh] overflow-auto">
                            <div className="sticky top-0 z-10 flex items-center py-2 px-2 text-xs font-medium text-muted-foreground uppercase border-b bg-background">
                                <div className="flex-1">Account</div>
                                <div className="flex gap-4 text-right tabular-nums">
                                    <div className="w-32">Current</div>
                                    {showComparison && (
                                        <>
                                            <div className="w-32">Comparison</div>
                                            <div className="w-28">Change</div>
                                            <div className="w-16">%</div>
                                        </>
                                    )}
                                </div>
                            </div>
                            {nodes.map((node) => (
                                <AccountRow key={`${expandKey}-${node.id}`} node={node} isExpanded={expandAll} showComparison={showComparison} />
                            ))}
                        </div>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export default function IncomeStatement({ fiscalYears, selectedYearId, comparisonYearId, report }: Props) {
    const selectedFiscalYear = fiscalYears.find((fy) => fy.id === selectedYearId);
    const selectedComparisonFiscalYear = comparisonYearId ? fiscalYears.find((fy) => fy.id === comparisonYearId) : undefined;

    const handleYearChange = (value: string) => {
        router.get(
            '/reports/income-statement',
            { fiscal_year_id: value, comparison_year_id: comparisonYearId },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleComparisonChange = (value: string) => {
        router.get(
            '/reports/income-statement',
            { fiscal_year_id: selectedYearId, comparison_year_id: value === 'none' ? undefined : value },
            { preserveState: true, preserveScroll: true },
        );
    };

    const totalRevenue = report.totals?.revenue || 0;
    const totalExpense = report.totals?.expense || 0;
    const netIncome = report.totals?.net_income || 0;
    const netIncomeComparison = report.totals?.comparison_net_income || 0;
    const netIncomeChange = report.totals?.change_net_income || 0;
    const netIncomeChangePct = report.totals?.change_percentage_net_income || 0;
    const isProfit = netIncome >= 0;

    return (
        <AppLayout breadcrumbs={[{ title: 'Reports', href: '#' }, { title: 'Income Statement', href: '/reports/income-statement' }]}>
            <Head title="Income Statement" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-bold tracking-tight">Income Statement</h1>
                        <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                            {selectedFiscalYear && (
                                <span>
                                    {selectedFiscalYear.name} • {selectedFiscalYear.status}
                                </span>
                            )}
                            <Badge variant="outline">
                                {selectedComparisonFiscalYear ? `Compare: ${selectedComparisonFiscalYear.name}` : 'Compare: None'}
                            </Badge>
                            <Badge
                                variant={isProfit ? 'secondary' : 'destructive'}
                                className={cn(isProfit && 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300')}
                            >
                                {isProfit ? 'Profit' : 'Loss'}
                            </Badge>
                        </div>
                    </div>
                    <div className="flex flex-col gap-3 sm:flex-row sm:gap-4">
                        <div className="w-full sm:w-[220px]">
                            <Select value={String(selectedYearId)} onValueChange={handleYearChange}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Fiscal Year" />
                                </SelectTrigger>
                                <SelectContent>
                                    {fiscalYears.map((fy) => (
                                        <SelectItem key={fy.id} value={String(fy.id)}>
                                            {fy.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="w-full sm:w-[220px]">
                            <Select value={comparisonYearId ? String(comparisonYearId) : 'none'} onValueChange={handleComparisonChange}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Compare With..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">None</SelectItem>
                                    {fiscalYears
                                        .filter((fy) => fy.id !== selectedYearId)
                                        .map((fy) => (
                                            <SelectItem key={fy.id} value={String(fy.id)}>
                                                {fy.name}
                                            </SelectItem>
                                        ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <div className="grid gap-6">
                    <Section
                        title="Revenue"
                        nodes={report.revenues || []}
                        total={report.totals?.revenue || 0}
                        comparisonTotal={report.totals?.comparison_revenue}
                        change={report.totals?.change_revenue}
                        changePercentage={report.totals?.change_percentage_revenue}
                        showComparison={!!comparisonYearId}
                    />

                    <Section
                        title="Expense"
                        nodes={report.expenses || []}
                        total={report.totals?.expense || 0}
                        comparisonTotal={report.totals?.comparison_expense}
                        change={report.totals?.change_expense}
                        changePercentage={report.totals?.change_percentage_expense}
                        showComparison={!!comparisonYearId}
                    />

                    <Card className={cn('overflow-hidden border-t-4', isProfit ? 'border-emerald-500' : 'border-destructive')}>
                        <CardHeader className="bg-muted/15">
                            <div className="flex items-start justify-between gap-3">
                                <div className="space-y-1">
                                    <CardTitle className="text-base">Summary</CardTitle>
                                    <CardDescription className="text-xs">Net Income = Total Revenue - Total Expense.</CardDescription>
                                </div>
                                <Badge
                                    variant={isProfit ? 'secondary' : 'destructive'}
                                    className={cn(isProfit && 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300')}
                                >
                                    {isProfit ? 'Profit' : 'Loss'}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <div className="grid gap-3 rounded-lg border bg-background p-4">
                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-sm text-muted-foreground">Total Revenue</span>
                                    <span className="text-sm font-semibold tabular-nums">{formatCurrency(totalRevenue)}</span>
                                </div>
                                <Separator />
                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-sm text-muted-foreground">Total Expense</span>
                                    <span className="text-sm font-semibold tabular-nums">{formatCurrency(totalExpense)}</span>
                                </div>
                                <Separator />
                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-sm text-muted-foreground">Net Income</span>
                                    <span className={cn('text-sm font-semibold tabular-nums', isProfit ? 'text-emerald-700 dark:text-emerald-300' : 'text-destructive')}>
                                        {formatCurrency(netIncome)}
                                    </span>
                                </div>
                                {!!comparisonYearId && (
                                    <>
                                        <Separator />
                                        <div className="flex items-center justify-between gap-4">
                                            <span className="text-sm text-muted-foreground">Net Income (Comparison)</span>
                                            <span className="text-sm font-semibold tabular-nums text-muted-foreground">
                                                {formatCurrency(netIncomeComparison)}
                                            </span>
                                        </div>
                                        <Separator />
                                        <div className="flex items-center justify-between gap-4">
                                            <span className="text-sm text-muted-foreground">Net Income Change</span>
                                            <span
                                                className={cn(
                                                    'text-sm font-semibold tabular-nums',
                                                    netIncomeChange < 0
                                                        ? 'text-red-500'
                                                        : netIncomeChange > 0
                                                          ? 'text-green-600'
                                                          : 'text-muted-foreground',
                                                )}
                                            >
                                                {formatCurrency(netIncomeChange)} ({netIncomeChangePct.toFixed(1)}%)
                                            </span>
                                        </div>
                                    </>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

