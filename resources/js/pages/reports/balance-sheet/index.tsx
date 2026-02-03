import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatCurrency, cn } from '@/lib/utils';
import { ChevronRight, ChevronDown } from 'lucide-react';
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
    children?: AccountNode[];
    level: number;
}

interface Props {
    fiscalYears: FiscalYear[];
    selectedYearId: number;
    report: {
        assets: AccountNode[];
        liabilities: AccountNode[];
        equity: AccountNode[];
        totals: {
            assets: number;
            liabilities: number;
            equity: number;
        };
    };
}

const AccountRow = ({ node, isExpanded = true }: { node: AccountNode; isExpanded?: boolean }) => {
    const [expanded, setExpanded] = useState(isExpanded);
    const hasChildren = node.children && node.children.length > 0;

    return (
        <div className="flex flex-col">
            <div className={cn(
                "flex items-center py-2 px-2 hover:bg-muted/50 rounded-sm text-sm",
                 hasChildren && "font-semibold"
            )}>
                <div 
                    className="flex items-center flex-1 gap-2 cursor-pointer"
                    onClick={() => hasChildren && setExpanded(!expanded)}
                    style={{ paddingLeft: `${(node.level - 1) * 1.5}rem` }}
                >
                    {hasChildren ? (
                        expanded ? <ChevronDown className="h-4 w-4 text-muted-foreground" /> : <ChevronRight className="h-4 w-4 text-muted-foreground" />
                    ) : (
                        <div className="w-4" /> // Spacer
                    )}
                    <span className="font-mono text-muted-foreground text-xs">{node.code}</span>
                    <span>{node.name}</span>
                </div>
                <div className="font-mono">
                    {formatCurrency(node.balance)}
                </div>
            </div>
            {hasChildren && expanded && (
                <div>
                    {node.children!.map((child) => (
                        <AccountRow key={child.id} node={child} />
                    ))}
                </div>
            )}
        </div>
    );
};

const Section = ({ title, nodes, total }: { title: string, nodes: AccountNode[], total: number }) => (
    <Card className="mb-6">
        <CardHeader className="pb-2">
            <div className="flex justify-between items-center">
                <CardTitle className="text-lg">{title}</CardTitle>
                <span className="text-lg font-bold">{formatCurrency(total)}</span>
            </div>
        </CardHeader>
        <CardContent>
            {nodes.length === 0 ? (
                <div className="text-muted-foreground italic py-4 text-center">No accounts found</div>
            ) : (
                <div className="space-y-1">
                    {nodes.map(node => (
                        <AccountRow key={node.id} node={node} />
                    ))}
                </div>
            )}
        </CardContent>
    </Card>
);

export default function BalanceSheet({ fiscalYears, selectedYearId, report }: Props) {
    const handleYearChange = (value: string) => {
        router.get(route('reports.balance-sheet'), { fiscal_year_id: value }, {
             preserveState: true,
             preserveScroll: true,
        });
    };

    // Calculate generic check
    const totalAssets = report.totals?.assets || 0;
    const totalLiabilitiesAndEquity = (report.totals?.liabilities || 0) + (report.totals?.equity || 0);
    const isBalanced = Math.abs(totalAssets - totalLiabilitiesAndEquity) < 1.0; // Allow small rounding error

    return (
        <AppLayout breadcrumbs={[{ label: 'Reports' }, { label: 'Balance Sheet' }]}>
            <Head title="Balance Sheet" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                 <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold tracking-tight">Balance Sheet</h1>
                    <div className="w-[200px]">
                        <Select
                            value={String(selectedYearId)}
                            onValueChange={handleYearChange}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select Fiscal Year" />
                            </SelectTrigger>
                            <SelectContent>
                                {fiscalYears.map((fy) => (
                                    <SelectItem key={fy.id} value={String(fy.id)}>
                                        {fy.name} ({fy.status})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="grid gap-6">
                    <Section 
                        title="Assets" 
                        nodes={report.assets || []} 
                        total={report.totals?.assets || 0} 
                    />

                    <div className="space-y-6">
                        <Section 
                            title="Liabilities" 
                            nodes={report.liabilities || []} 
                            total={report.totals?.liabilities || 0} 
                        />
                        
                        <Section 
                            title="Equity" 
                            nodes={report.equity || []} 
                            total={report.totals?.equity || 0} 
                        />
                    </div>

                    <Card className={cn("border-t-4", isBalanced ? "border-green-500" : "border-destructive")}>
                        <CardHeader>
                            <CardTitle>Summary</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex justify-between items-center text-lg">
                                <span>Total Assets</span>
                                <span className="font-bold">{formatCurrency(totalAssets)}</span>
                            </div>
                            <div className="flex justify-between items-center text-lg mt-2">
                                <span>Total Liabilities & Equity</span>
                                <span className="font-bold">{formatCurrency(totalLiabilitiesAndEquity)}</span>
                            </div>
                             {!isBalanced && (
                                <div className="mt-4 p-2 bg-destructive/10 text-destructive rounded-md text-center">
                                    <strong>Unbalanced!</strong> Difference: {formatCurrency(Math.abs(totalAssets - totalLiabilitiesAndEquity))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
