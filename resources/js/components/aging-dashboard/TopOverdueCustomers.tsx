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
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
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
                                <TableHead className="text-right">
                                    Invoices
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
                                    <TableCell className="text-right tabular-nums">
                                        {formatCurrency(
                                            customer.outstanding_amount,
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {customer.invoice_count}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
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
