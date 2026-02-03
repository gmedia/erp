import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
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
    const isBalanced = Math.abs(totalDebit - totalCredit) < 0.01;

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
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold tracking-tight">Trial Balance</h1>
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

                <Card className="flex-1">
                    <CardHeader>
                        <CardTitle>Trial Balance Report</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[100px]">Code</TableHead>
                                        <TableHead>Account Name</TableHead>
                                        <TableHead className="w-[100px]">Type</TableHead>
                                        <TableHead className="text-right">Debit</TableHead>
                                        <TableHead className="text-right">Credit</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {report.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={5} className="text-center h-24">
                                                No data available for the selected fiscal year.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        report.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="font-mono">{item.code}</TableCell>
                                                <TableCell>
                                                    <div style={{ paddingLeft: `${(item.level - 1) * 1.5}rem` }}>
                                                        {item.name}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="capitalize">{item.type}</TableCell>
                                                <TableCell className="text-right">
                                                    {item.debit !== 0 ? formatCurrency(item.debit) : '-'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {item.credit !== 0 ? formatCurrency(item.credit) : '-'}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                                {report.length > 0 && (
                                    <TableBody className="border-t-2 border-primary/20 bg-muted/50 font-bold">
                                        <TableRow>
                                            <TableCell colSpan={3} className="text-right">Total</TableCell>
                                            <TableCell className={cn("text-right", !isBalanced && "text-destructive")}>
                                                {formatCurrency(totalDebit)}
                                            </TableCell>
                                            <TableCell className={cn("text-right", !isBalanced && "text-destructive")}>
                                                {formatCurrency(totalCredit)}
                                            </TableCell>
                                        </TableRow>
                                        {!isBalanced && (
                                            <TableRow>
                                                <TableCell colSpan={5} className="text-center text-destructive">
                                                    Warning: Trial Balance is not balanced! Difference: {formatCurrency(Math.abs(totalDebit - totalCredit))}
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                )}
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
