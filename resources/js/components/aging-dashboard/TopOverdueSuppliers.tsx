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
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { Building2 } from 'lucide-react';
import { memo } from 'react';
import type { TopOverdueSupplier } from '../../hooks/useAgingDashboard';

interface TopOverdueSuppliersProps {
    readonly suppliers: TopOverdueSupplier[];
    readonly isLoading?: boolean;
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
                        <Building2 className="h-8 w-8 opacity-50" />
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
                                <TableHead className="hidden text-right 2xl:table-cell">
                                    Bills
                                </TableHead>
                                <TableHead className="hidden text-right 2xl:table-cell">
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
                                    <TableCell className="hidden text-right tabular-nums 2xl:table-cell">
                                        {supplier.bill_count}
                                    </TableCell>
                                    <TableCell className="hidden text-right tabular-nums 2xl:table-cell">
                                        {formatDateByRegionalSettings(
                                            supplier.oldest_due_date,
                                        )}
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
