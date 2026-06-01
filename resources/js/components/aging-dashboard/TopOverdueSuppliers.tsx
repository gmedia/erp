import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatCurrency } from '@/lib/utils';
import { memo } from 'react';
import type { TopOverdueSupplier } from '../../hooks/useAgingDashboard';

interface TopOverdueSuppliersProps {
    readonly suppliers: TopOverdueSupplier[];
    readonly isLoading?: boolean;
}

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
    }).format(date);
}

export const TopOverdueSuppliers = memo<TopOverdueSuppliersProps>(
    function TopOverdueSuppliers({ suppliers, isLoading }) {
        if (isLoading) {
            return (
                <Card className="flex h-[400px] w-full flex-col">
                    <CardHeader className="pb-2">
                        <Skeleton className="h-6 w-48" />
                        <Skeleton className="mt-1 h-4 w-64" />
                    </CardHeader>
                    <CardContent className="flex-1 space-y-2">
                        {[1, 2, 3, 4, 5].map((i) => (
                            <Skeleton key={i} className="h-10 w-full" />
                        ))}
                    </CardContent>
                </Card>
            );
        }

        if (suppliers.length === 0) {
            return (
                <Card className="flex h-[400px] w-full flex-col items-center justify-center p-6 text-center text-muted-foreground">
                    <div className="mb-4 rounded-full bg-muted p-4">
                        <svg
                            className="h-8 w-8 opacity-50"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                            />
                        </svg>
                    </div>
                    No overdue suppliers.
                </Card>
            );
        }

        return (
            <Card className="flex h-[400px] w-full flex-col">
                <CardHeader className="pb-2">
                    <CardTitle>Top Overdue Suppliers</CardTitle>
                    <p className="text-sm text-muted-foreground">
                        By overdue amount, max 10
                    </p>
                </CardHeader>
                <CardContent className="flex-1 overflow-auto pb-4">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Supplier</TableHead>
                                <TableHead className="text-right">
                                    Overdue Amount
                                </TableHead>
                                <TableHead className="text-right">
                                    Outstanding
                                </TableHead>
                                <TableHead className="text-right">
                                    Bills
                                </TableHead>
                                <TableHead className="text-right">
                                    Oldest Due
                                </TableHead>
                                <TableHead className="text-right">
                                    Days Late
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {suppliers.map((supplier) => (
                                <TableRow key={supplier.supplier_id}>
                                    <TableCell className="font-medium">
                                        {supplier.supplier_name}
                                    </TableCell>
                                    <TableCell className="text-right font-semibold tabular-nums">
                                        {formatCurrency(
                                            supplier.overdue_amount,
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatCurrency(
                                            supplier.outstanding_amount,
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {supplier.bill_count}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatDate(supplier.oldest_due_date)}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {supplier.max_days_overdue}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        );
    },
);
