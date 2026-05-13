'use client';

import { memo } from 'react';

import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';

const currencyOpts = { locale: 'id-ID', currency: 'IDR' } as const;

interface LineData {
    id?: number;
    account_code?: string;
    account_name?: string;
    debit: number;
    credit: number;
    memo?: string;
}

interface RecurringJournalLinesTableProps {
    lines: LineData[];
    totalDebit: number;
    totalCredit: number;
    actions?: (index: number) => React.ReactNode;
    emptyMessage?: string;
}

export const RecurringJournalLinesTable = memo<RecurringJournalLinesTableProps>(
    function RecurringJournalLinesTable({
        lines,
        totalDebit,
        totalCredit,
        actions,
        emptyMessage = 'No lines found.',
    }) {
        return (
            <div className="min-w-0 rounded-md border">
                <Table className="min-w-[600px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Account</TableHead>
                            <TableHead className="text-right">Debit</TableHead>
                            <TableHead className="text-right">Credit</TableHead>
                            <TableHead>Memo</TableHead>
                            {actions && (
                                <TableHead className="w-[100px]">
                                    Actions
                                </TableHead>
                            )}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {lines.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={actions ? 5 : 4}
                                    className="text-center text-muted-foreground"
                                >
                                    {emptyMessage}
                                </TableCell>
                            </TableRow>
                        ) : (
                            lines.map((line, index) => (
                                <TableRow key={line.id ?? index}>
                                    <TableCell>
                                        <div>{line.account_code}</div>
                                        <div className="text-sm text-gray-500">
                                            {line.account_name}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {formatCurrencyByRegionalSettings(
                                            Number(line.debit || 0),
                                            currencyOpts,
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {formatCurrencyByRegionalSettings(
                                            Number(line.credit || 0),
                                            currencyOpts,
                                        )}
                                    </TableCell>
                                    <TableCell>{line.memo || '-'}</TableCell>
                                    {actions && (
                                        <TableCell>{actions(index)}</TableCell>
                                    )}
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                    <TableFooter>
                        <TableRow>
                            <TableCell className="font-semibold">
                                Total
                            </TableCell>
                            <TableCell className="text-right font-semibold">
                                {formatCurrencyByRegionalSettings(
                                    totalDebit,
                                    currencyOpts,
                                )}
                            </TableCell>
                            <TableCell className="text-right font-semibold">
                                {formatCurrencyByRegionalSettings(
                                    totalCredit,
                                    currencyOpts,
                                )}
                            </TableCell>
                            <TableCell colSpan={actions ? 2 : 1} />
                        </TableRow>
                    </TableFooter>
                </Table>
            </div>
        );
    },
);
