import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatCurrency, cn } from '@/lib/utils';
import { AlertTriangle, ChevronRight, ChevronDown } from 'lucide-react';
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
        assets: AccountNode[];
        liabilities: AccountNode[];
        equity: AccountNode[];
        totals: {
            assets: number;
            liabilities: number;
            equity: number;
            comparison_assets?: number;
            comparison_liabilities?: number;
            comparison_equity?: number;
            change_assets?: number;
            change_percentage_assets?: number;
            change_liabilities?: number;
            change_percentage_liabilities?: number;
            change_equity?: number;
            change_percentage_equity?: number;
        };
    };
}

const AccountRow = ({ node, isExpanded = true, showComparison = false }: { node: AccountNode; isExpanded?: boolean, showComparison?: boolean }) => {
    const [expanded, setExpanded] = useState(isExpanded);
    const hasChildren = node.children && node.children.length > 0;
    const changeValue = node.change || 0;

    return (
        <div className="flex flex-col">
            <div className={cn(
                "flex items-center gap-2 py-2 px-2 text-sm border-b border-border/40 hover:bg-muted/40",
                hasChildren && "bg-muted/20 font-semibold"
            )}>
                <div 
                    className="flex items-center flex-1 gap-2 cursor-pointer"
                    onClick={() => hasChildren && setExpanded(!expanded)}
                    style={{ paddingLeft: `${(node.level - 1) * 1.5}rem` }}
                >
                    {hasChildren ? (
                        expanded ? <ChevronDown className="h-4 w-4 text-muted-foreground" /> : <ChevronRight className="h-4 w-4 text-muted-foreground" />
                    ) : (
                        <div className="w-4" />
                    )}
                    <span className="font-mono text-muted-foreground text-xs">{node.code}</span>
                    <span className="truncate">{node.name}</span>
                </div>
                <div className="flex gap-4 text-right tabular-nums">
                    <div className="w-32 font-mono">
                        {formatCurrency(node.balance)}
                    </div>
                    {showComparison && (
                        <>
                            <div className="w-32 font-mono text-muted-foreground">
                                {formatCurrency(node.comparison_balance || 0)}
                            </div>
                            <div className={cn("w-28 font-mono", changeValue < 0 ? "text-red-500" : changeValue > 0 ? "text-green-600" : "text-muted-foreground")}>
                                {formatCurrency(changeValue)}
                            </div>
                             <div className={cn("w-16 font-mono", changeValue < 0 ? "text-red-500" : changeValue > 0 ? "text-green-600" : "text-muted-foreground")}>
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
                                <span className="w-32 text-lg font-bold text-muted-foreground">{formatCurrency(comparisonTotal || 0)}</span>
                                <span
                                    className={cn(
                                        "w-28 text-lg font-bold",
                                        changeValue < 0 ? "text-red-500" : changeValue > 0 ? "text-green-600" : "text-muted-foreground"
                                    )}
                                >
                                    {formatCurrency(changeValue)}
                                </span>
                                <span
                                    className={cn(
                                        "w-16 text-lg font-bold",
                                        changeValue < 0 ? "text-red-500" : changeValue > 0 ? "text-green-600" : "text-muted-foreground"
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

export default function BalanceSheet({ fiscalYears, selectedYearId, comparisonYearId, report }: Props) {
    const selectedFiscalYear = fiscalYears.find((fy) => fy.id === selectedYearId);
    const selectedComparisonFiscalYear = comparisonYearId
        ? fiscalYears.find((fy) => fy.id === comparisonYearId)
        : undefined;

    const handleYearChange = (value: string) => {
        router.get('/reports/balance-sheet', { fiscal_year_id: value, comparison_year_id: comparisonYearId }, {
             preserveState: true,
             preserveScroll: true,
        });
    };

    const handleComparisonChange = (value: string) => {
         router.get('/reports/balance-sheet', { fiscal_year_id: selectedYearId, comparison_year_id: value === 'none' ? undefined : value }, {
             preserveState: true,
             preserveScroll: true,
        });
    };

    // Calculate generic check
    const totalAssets = report.totals?.assets || 0;
    const totalLiabilitiesAndEquity = (report.totals?.liabilities || 0) + (report.totals?.equity || 0);
    const isBalanced = Math.abs(totalAssets - totalLiabilitiesAndEquity) < 1.0;
    const difference = Math.abs(totalAssets - totalLiabilitiesAndEquity);

    return (
        <AppLayout breadcrumbs={[{ title: 'Reports', href: '#' }, { title: 'Balance Sheet', href: '/reports/balance-sheet' }]}>
            <Head title="Balance Sheet" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                 <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-bold tracking-tight">Balance Sheet</h1>
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
                                variant={isBalanced ? "secondary" : "destructive"}
                                className={cn(
                                    isBalanced && "border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
                                )}
                            >
                                {isBalanced ? 'Balanced' : `Unbalanced • ${formatCurrency(difference)}`}
                            </Badge>
                        </div>
                    </div>
                    <div className="flex flex-col gap-3 sm:flex-row sm:gap-4">
                        <div className="w-full sm:w-[220px]">
                            <Select
                                value={String(selectedYearId)}
                                onValueChange={handleYearChange}
                            >
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
                            <Select
                                value={comparisonYearId ? String(comparisonYearId) : 'none'}
                                onValueChange={handleComparisonChange}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Compare With..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">None</SelectItem>
                                    {fiscalYears.filter(fy => fy.id !== selectedYearId).map((fy) => (
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
                        title="Assets" 
                        nodes={report.assets || []} 
                        total={report.totals?.assets || 0}
                        comparisonTotal={report.totals?.comparison_assets}
                        change={report.totals?.change_assets}
                        changePercentage={report.totals?.change_percentage_assets}
                        showComparison={!!comparisonYearId} 
                    />

                    <div className="space-y-6">
                        <Section 
                            title="Liabilities" 
                            nodes={report.liabilities || []} 
                            total={report.totals?.liabilities || 0} 
                            comparisonTotal={report.totals?.comparison_liabilities}
                            change={report.totals?.change_liabilities}
                            changePercentage={report.totals?.change_percentage_liabilities}
                            showComparison={!!comparisonYearId} 
                        />
                        
                        <Section 
                            title="Equity" 
                            nodes={report.equity || []} 
                            total={report.totals?.equity || 0} 
                            comparisonTotal={report.totals?.comparison_equity}
                            change={report.totals?.change_equity}
                            changePercentage={report.totals?.change_percentage_equity}
                            showComparison={!!comparisonYearId} 
                        />
                    </div>

                    <Card
                        className={cn(
                            'overflow-hidden border-t-4',
                            isBalanced ? 'border-emerald-500' : 'border-destructive'
                        )}
                    >
                        <CardHeader className="bg-muted/15">
                            <div className="flex items-start justify-between gap-3">
                                <div className="space-y-1">
                                    <CardTitle className="text-base">Summary</CardTitle>
                                    <CardDescription className="text-xs">
                                        Assets should equal liabilities plus equity.
                                    </CardDescription>
                                </div>
                                <Badge
                                    variant={isBalanced ? 'secondary' : 'destructive'}
                                    className={cn(
                                        isBalanced &&
                                            'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                    )}
                                >
                                    {isBalanced ? 'Balanced' : `Unbalanced • ${formatCurrency(difference)}`}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <div className="grid gap-3 rounded-lg border bg-background p-4">
                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-sm text-muted-foreground">Total Assets</span>
                                    <span className="text-sm font-semibold tabular-nums">
                                        {formatCurrency(totalAssets)}
                                    </span>
                                </div>
                                <Separator />
                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-sm text-muted-foreground">
                                        Total Liabilities &amp; Equity
                                    </span>
                                    <span className="text-sm font-semibold tabular-nums">
                                        {formatCurrency(totalLiabilitiesAndEquity)}
                                    </span>
                                </div>
                            </div>

                            {!isBalanced && (
                                <Alert
                                    variant="destructive"
                                    className="border-destructive/40 bg-destructive/10 text-destructive"
                                >
                                    <AlertTriangle className="h-4 w-4" />
                                    <AlertTitle>Unbalanced</AlertTitle>
                                    <AlertDescription>
                                        Difference:{' '}
                                        <span className="font-medium tabular-nums">
                                            {formatCurrency(difference)}
                                        </span>
                                    </AlertDescription>
                                </Alert>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
