import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency } from '@/lib/utils';

interface FiscalYear {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    status: string;
}

interface CashFlowItem {
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

interface Props {
    fiscalYears: FiscalYear[];
    selectedYearId: number;
    report: CashFlowItem[];
}

export default function CashFlow({ fiscalYears, selectedYearId, report }: Props) {
    const totalInflow = report.reduce((sum, item) => sum + item.inflow, 0);
    const totalOutflow = report.reduce((sum, item) => sum + item.outflow, 0);
    const netCashFlow = totalInflow - totalOutflow;

    const selectedFiscalYear = fiscalYears.find((fy) => fy.id === selectedYearId);
    const parentIds = new Set(report.map((item) => item.parent_id).filter((id): id is number => id != null));

    const handleYearChange = (value: string) => {
        router.get('/reports/cash-flow', { fiscal_year_id: value }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Reports', href: '#' }, { title: 'Cash Flow', href: '/reports/cash-flow' }]}>
            <Head title="Cash Flow" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-bold tracking-tight">Cash Flow</h1>
                        {selectedFiscalYear && (
                            <div className="text-sm text-muted-foreground">
                                {selectedFiscalYear.name} • {selectedFiscalYear.status}
                            </div>
                        )}
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

                <Card className="flex-1">
                    <CardHeader>
                        <CardTitle>Cash Flow Report</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="rounded-md border overflow-hidden">
                            <div className="max-h-[calc(100vh-22rem)] overflow-auto">
                                <Table className="min-w-[760px]">
                                    <TableHeader className="sticky top-0 z-10 bg-background">
                                        <TableRow>
                                            <TableHead className="w-[120px]">Code</TableHead>
                                            <TableHead>Account Name</TableHead>
                                            <TableHead className="hidden w-[120px] md:table-cell">Type</TableHead>
                                            <TableHead className="text-right tabular-nums">Inflow</TableHead>
                                            <TableHead className="text-right tabular-nums">Outflow</TableHead>
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
                                                        className={hasChildren ? 'bg-muted/20 font-medium' : 'odd:bg-muted/10'}
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
                                                            {item.inflow !== 0 ? formatCurrency(item.inflow) : '-'}
                                                        </TableCell>
                                                        <TableCell className="text-right tabular-nums">
                                                            {item.outflow !== 0 ? formatCurrency(item.outflow) : '-'}
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
                                                <TableCell className="text-right font-semibold tabular-nums">
                                                    {formatCurrency(totalInflow)}
                                                </TableCell>
                                                <TableCell className="text-right font-semibold tabular-nums">
                                                    {formatCurrency(totalOutflow)}
                                                </TableCell>
                                            </TableRow>
                                        </TableFooter>
                                    )}
                                </Table>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

