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
import { Users } from 'lucide-react';
import { memo } from 'react';
import type { TopOverdueCustomer } from '../../hooks/useAgingDashboard';

interface TopOverdueCustomersProps {
    readonly customers: TopOverdueCustomer[];
    readonly isLoading?: boolean;
}

export const TopOverdueCustomers = memo<TopOverdueCustomersProps>(
    function TopOverdueCustomers({ customers, isLoading }) {
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

        if (customers.length === 0) {
            return (
                <Card className="flex h-[400px] w-full flex-col items-center justify-center p-6 text-center text-muted-foreground">
                    <div className="mb-4 rounded-full bg-muted p-4">
                        <Users className="h-8 w-8 opacity-50" />
                    </div>
                    No overdue customers.
                </Card>
            );
        }

        return (
            <Card className="flex h-[400px] w-full flex-col">
                <CardHeader className="pb-2">
                    <CardTitle>Top Overdue Customers</CardTitle>
                    <p className="text-sm text-muted-foreground">
                        By overdue amount, max 10
                    </p>
                </CardHeader>
                <CardContent className="flex-1 overflow-auto pb-4">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Customer</TableHead>
                                <TableHead className="text-right">
                                    Overdue Amount
                                </TableHead>
                                <TableHead className="text-right">
                                    Outstanding
                                </TableHead>
                                <TableHead className="hidden text-right 2xl:table-cell">
                                    Invoices
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
                            {customers.map((customer) => (
                                <TableRow key={customer.customer_id}>
                                    <TableCell className="font-medium">
                                        {customer.customer_name}
                                    </TableCell>
                                    <TableCell className="text-right font-semibold tabular-nums">
                                        {formatCurrency(
                                            customer.overdue_amount,
                                        )}
                                    </TableCell>
                                    <TableCell className="hidden text-right tabular-nums 2xl:table-cell">
                                        {formatCurrency(
                                            customer.outstanding_amount,
                                        )}
                                    </TableCell>
                                    <TableCell className="hidden text-right tabular-nums 2xl:table-cell">
                                        {customer.invoice_count}
                                    </TableCell>
                                    <TableCell className="hidden text-right tabular-nums 2xl:table-cell">
                                        {formatDateByRegionalSettings(
                                            customer.oldest_due_date,
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {customer.max_days_overdue}
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
