import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { cn, formatCurrency } from '@/lib/utils';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { useState } from 'react';

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

export const getChangeTextClass = (value: number): string => {
    if (value < 0) {
        return 'text-red-500';
    }

    if (value > 0) {
        return 'text-green-600';
    }

    return 'text-muted-foreground';
};

function AccountRow({
    node,
    isExpanded = true,
    showComparison = false,
}: Readonly<{
    node: ReportAccountNode;
    isExpanded?: boolean;
    showComparison?: boolean;
}>) {
    const [expanded, setExpanded] = useState(isExpanded);
    const hasChildren = node.children && node.children.length > 0;
    const changeValue = node.change || 0;
    const changeTextClass = getChangeTextClass(changeValue);
    const expandIcon = expanded ? (
        <ChevronDown className="h-4 w-4 text-muted-foreground" />
    ) : (
        <ChevronRight className="h-4 w-4 text-muted-foreground" />
    );

    return (
        <div className="flex flex-col">
            <div
                className={cn(
                    'flex items-center gap-2 border-b border-border/40 px-2 py-2 text-sm hover:bg-muted/40',
                    hasChildren && 'bg-muted/20 font-semibold',
                )}
            >
                <button
                    type="button"
                    className="flex flex-1 cursor-pointer items-center gap-2 text-left"
                    onClick={() => hasChildren && setExpanded(!expanded)}
                    style={{ paddingLeft: `${(node.level - 1) * 1.5}rem` }}
                >
                    {hasChildren ? expandIcon : <div className="w-4" />}
                    <span className="font-mono text-xs text-muted-foreground">
                        {node.code}
                    </span>
                    <span className="truncate">{node.name}</span>
                </button>
                <div className="flex gap-4 text-right tabular-nums">
                    <div className="w-32">{formatCurrency(node.balance)}</div>
                    {showComparison && (
                        <>
                            <div className="w-32 text-muted-foreground">
                                {formatCurrency(node.comparison_balance || 0)}
                            </div>
                            <div className={cn('w-28', changeTextClass)}>
                                {formatCurrency(changeValue)}
                            </div>
                            <div className={cn('w-16', changeTextClass)}>
                                {(node.change_percentage || 0).toFixed(1)}%
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
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

export function FinancialReportSection({
    title,
    nodes,
    total,
    comparisonTotal,
    change,
    changePercentage,
    showComparison,
}: Readonly<{
    title: string;
    nodes: ReportAccountNode[];
    total: number;
    comparisonTotal?: number;
    change?: number;
    changePercentage?: number;
    showComparison?: boolean;
}>) {
    const [expandAll, setExpandAll] = useState(true);
    const [expandKey, setExpandKey] = useState(0);
    const changeValue = change || 0;
    const changeTextClass = getChangeTextClass(changeValue);

    const setExpanded = (value: Readonly<boolean>) => {
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
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setExpanded(true)}
                                disabled={expandAll}
                            >
                                Expand all
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setExpanded(false)}
                                disabled={!expandAll}
                            >
                                Collapse all
                            </Button>
                        </div>
                    </div>
                    <div className="flex gap-4 text-right tabular-nums">
                        <span className="w-32 text-lg font-bold">
                            {formatCurrency(total)}
                        </span>
                        {showComparison && (
                            <>
                                <span className="w-32 text-lg font-bold text-muted-foreground">
                                    {formatCurrency(comparisonTotal || 0)}
                                </span>
                                <span
                                    className={cn(
                                        'w-28 text-lg font-bold',
                                        changeTextClass,
                                    )}
                                >
                                    {formatCurrency(changeValue)}
                                </span>
                                <span
                                    className={cn(
                                        'w-16 text-lg font-bold',
                                        changeTextClass,
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
                    <div className="py-4 text-center text-muted-foreground italic">
                        No accounts found
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-md border">
                        <ScrollArea className="max-h-[60vh]">
                            <div className="sticky top-0 z-10 flex items-center border-b bg-background px-2 py-2 text-xs font-medium text-muted-foreground uppercase">
                                <div className="flex-1">Account</div>
                                <div className="flex gap-4 text-right tabular-nums">
                                    <div className="w-32">Current</div>
                                    {showComparison && (
                                        <>
                                            <div className="w-32">
                                                Comparison
                                            </div>
                                            <div className="w-28">Change</div>
                                            <div className="w-16">%</div>
                                        </>
                                    )}
                                </div>
                            </div>
                            {nodes.map((node) => (
                                <AccountRow
                                    key={`${expandKey}-${node.id}`}
                                    node={node}
                                    isExpanded={expandAll}
                                    showComparison={showComparison}
                                />
                            ))}
                            <ScrollBar orientation="horizontal" />
                        </ScrollArea>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
