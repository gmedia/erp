import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatCurrency } from '@/lib/utils';
import { cn } from '@/lib/utils';

interface FiscalYear {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    status: string;
}

interface AccountItem {
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

interface Props {
    fiscalYears: FiscalYear[];
    selectedYearId: number;
    report: AccountItem[];
}

export default function TrialBalance({ fiscalYears, selectedYearId, report }: Props) {
    const totalDebit = report.reduce((sum, item) => sum + item.debit, 0);
    const totalCredit = report.reduce((sum, item) => sum + item.credit, 0);
    const difference = Math.abs(totalDebit - totalCredit);
    const isBalanced = Math.abs(totalDebit - totalCredit) < 0.01;

    const selectedFiscalYear = fiscalYears.find((fy) => fy.id === selectedYearId);
    const parentIds = new Set(report.map((item) => item.parent_id).filter((id): id is number => id != null));

    const handleYearChange = (value: string) => {
        router.get('/reports/trial-balance', { fiscal_year_id: value }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Reports', href: '#' }, { title: 'Trial Balance', href: '/reports/trial-balance' }]}>
            <Head title="Trial Balance" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-bold tracking-tight">Trial Balance</h1>
                        <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                            {selectedFiscalYear && (
                                <span>
                                    {selectedFiscalYear.name} • {selectedFiscalYear.status}
                                </span>
                            )}
                            {report.length > 0 && (
                                <Badge
                                    variant={isBalanced ? 'secondary' : 'destructive'}
                                    className={cn(
                                        isBalanced && "border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
                                    )}
                                >
                                    {isBalanced ? 'Balanced' : `Unbalanced • ${formatCurrency(difference)}`}
                                </Badge>
                            )}
                        </div>
                    </div>
                    <div className="w-full sm:w-[240px]">
                        <Select value={String(selectedYearId)} onValueChange={handleYearChange}>
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

                <Card className="flex-1">
                    <CardHeader>
                        <CardTitle>Trial Balance Report</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="rounded-md border overflow-hidden">
                            <div className="max-h-[calc(100vh-18rem)] overflow-auto">
                                <Table className="min-w-[760px]">
                                    <TableHeader className="sticky top-0 z-10 bg-background">
                                        <TableRow>
                                            <TableHead className="w-[120px]">Code</TableHead>
                                            <TableHead>Account Name</TableHead>
                                            <TableHead className="hidden w-[120px] md:table-cell">Type</TableHead>
                                            <TableHead className="text-right tabular-nums">Debit</TableHead>
                                            <TableHead className="text-right tabular-nums">Credit</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {report.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={5} className="h-24 text-center text-muted-foreground">
                                                    No data available for the selected fiscal year.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            report.map((item) => {
                                                const hasChildren = parentIds.has(item.id);
                                                return (
                                                    <TableRow
                                                        key={item.id}
                                                        className={cn("odd:bg-muted/10", hasChildren && "bg-muted/20 font-medium")}
                                                    >
                                                        <TableCell className="font-mono text-xs text-muted-foreground">{item.code}</TableCell>
                                                        <TableCell>
                                                            <div
                                                                className="truncate"
                                                                style={{ paddingLeft: `${Math.max(0, item.level - 1) * 1.25}rem` }}
                                                            >
                                                                {item.name}
                                                            </div>
                                                        </TableCell>
                                                        <TableCell className="hidden capitalize text-muted-foreground md:table-cell">
                                                            {item.type}
                                                        </TableCell>
                                                        <TableCell className="text-right tabular-nums">
                                                            {item.debit !== 0 ? formatCurrency(item.debit) : '-'}
                                                        </TableCell>
                                                        <TableCell className="text-right tabular-nums">
                                                            {item.credit !== 0 ? formatCurrency(item.credit) : '-'}
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            })
                                        )}
                                    </TableBody>
                                    {report.length > 0 && (
                                        <TableFooter>
                                            <TableRow>
                                                <TableCell colSpan={2} className="text-right font-semibold">
                                                    Total
                                                </TableCell>
                                                <TableCell className="hidden md:table-cell" />
                                                <TableCell className={cn("text-right font-semibold tabular-nums", !isBalanced && "text-destructive")}>
                                                    {formatCurrency(totalDebit)}
                                                </TableCell>
                                                <TableCell className={cn("text-right font-semibold tabular-nums", !isBalanced && "text-destructive")}>
                                                    {formatCurrency(totalCredit)}
                                                </TableCell>
                                            </TableRow>
                                        </TableFooter>
                                    )}
                                </Table>
                            </div>
                        </div>
                        {!isBalanced && report.length > 0 && (
                            <div className="mt-3 rounded-md border border-destructive/30 bg-destructive/5 px-3 py-2 text-sm text-destructive">
                                Trial Balance tidak seimbang. Selisih: {formatCurrency(difference)}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
