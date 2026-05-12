import { memo } from 'react';

import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { RecurringJournal } from '@/types/recurring-journal';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';

interface RecurringJournalViewModalProps {
    item: RecurringJournal | null;
    open: boolean;
    onClose: () => void;
}

const frequencyLabels: Record<string, string> = {
    daily: 'Daily',
    weekly: 'Weekly',
    monthly: 'Monthly',
    quarterly: 'Quarterly',
    yearly: 'Yearly',
};

const currencyOpts = { locale: 'id-ID', currency: 'IDR' } as const;

export const RecurringJournalViewModal = memo<RecurringJournalViewModalProps>(
    function RecurringJournalViewModal({ item, open, onClose }) {
        if (!item) return null;

        const totalDebit = item.lines.reduce(
            (sum, line) => sum + Number(line.debit),
            0,
        );
        const totalCredit = item.lines.reduce(
            (sum, line) => sum + Number(line.credit),
            0,
        );

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="Recurring Journal Details"
                description="View complete details of this recurring journal"
                contentClassName="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-7xl"
            >
                <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                    <div className="space-y-6 py-2">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <ViewField label="Name" value={item.name} />
                            <ViewField
                                label="Frequency"
                                value={
                                    frequencyLabels[item.frequency] ||
                                    item.frequency
                                }
                            />
                            <ViewField
                                label="Next Run Date"
                                value={formatDateByRegionalSettings(
                                    item.next_run_date,
                                )}
                            />
                            <ViewField
                                label="Last Run Date"
                                value={
                                    item.last_run_date
                                        ? formatDateByRegionalSettings(
                                              item.last_run_date,
                                          )
                                        : '-'
                                }
                            />
                            <ViewField
                                label="Auto Post"
                                value={
                                    <Badge
                                        variant={
                                            item.auto_post
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {item.auto_post ? 'Yes' : 'No'}
                                    </Badge>
                                }
                            />
                            <ViewField
                                label="Status"
                                value={
                                    <Badge
                                        variant={
                                            item.is_active
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {item.is_active ? 'Active' : 'Inactive'}
                                    </Badge>
                                }
                            />
                            <ViewField
                                label="Total Amount"
                                value={formatCurrencyByRegionalSettings(
                                    item.total_amount,
                                    currencyOpts,
                                )}
                            />
                            <ViewField
                                label="Description"
                                value={item.description_template || '-'}
                            />
                        </div>

                        <div className="min-w-0 rounded-md border">
                            <Table className="min-w-[600px]">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Account</TableHead>
                                        <TableHead className="text-right">
                                            Debit
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Credit
                                        </TableHead>
                                        <TableHead>Memo</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {item.lines.map((line) => (
                                        <TableRow key={line.id}>
                                            <TableCell>
                                                <div>{line.account_code}</div>
                                                <div className="text-sm text-gray-500">
                                                    {line.account_name}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {formatCurrencyByRegionalSettings(
                                                    line.debit,
                                                    currencyOpts,
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {formatCurrencyByRegionalSettings(
                                                    line.credit,
                                                    currencyOpts,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {line.memo || '-'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
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
                                        <TableCell />
                                    </TableRow>
                                </TableFooter>
                            </Table>
                        </div>
                    </div>
                </div>
            </ViewModalShell>
        );
    },
);
