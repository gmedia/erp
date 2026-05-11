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

function getFrequencyLabel(frequency: RecurringJournal['frequency']) {
    const labels = {
        daily: 'Daily',
        weekly: 'Weekly',
        monthly: 'Monthly',
        quarterly: 'Quarterly',
        yearly: 'Yearly',
    };
    return labels[frequency] || frequency;
}

export function RecurringJournalViewModal({
    item,
    open,
    onClose,
}: Readonly<RecurringJournalViewModalProps>) {
    if (!item) return null;

    const totalDebit = item.lines.reduce((sum, line) => sum + Number(line.debit), 0);
    const totalCredit = item.lines.reduce((sum, line) => sum + Number(line.credit), 0);

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
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-500">
                                Name
                            </p>
                            <p>{item.name}</p>
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-500">
                                Frequency
                            </p>
                            <p>{getFrequencyLabel(item.frequency)}</p>
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-500">
                                Next Run Date
                            </p>
                            <p>
                                {formatDateByRegionalSettings(item.next_run_date)}
                            </p>
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-500">
                                Last Run Date
                            </p>
                            <p>
                                {item.last_run_date
                                    ? formatDateByRegionalSettings(item.last_run_date)
                                    : '-'}
                            </p>
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-500">
                                Auto Post
                            </p>
                            <Badge variant={item.auto_post ? 'default' : 'secondary'}>
                                {item.auto_post ? 'Yes' : 'No'}
                            </Badge>
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-500">
                                Status
                            </p>
                            <Badge variant={item.is_active ? 'default' : 'secondary'}>
                                {item.is_active ? 'Active' : 'Inactive'}
                            </Badge>
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-500">
                                Reference Template
                            </p>
                            <p>{item.reference_template || '-'}</p>
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-500">
                                Total Amount
                            </p>
                            <p>
                                {formatCurrencyByRegionalSettings(item.total_amount, {
                                    locale: 'id-ID',
                                    currency: 'IDR',
                                })}
                            </p>
                        </div>
                        <div className="min-w-0 sm:col-span-2">
                            <p className="text-sm font-medium text-gray-500">
                                Description Template
                            </p>
                            <p>{item.description_template}</p>
                        </div>
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
                                                {
                                                    locale: 'id-ID',
                                                    currency: 'IDR',
                                                },
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatCurrencyByRegionalSettings(
                                                line.credit,
                                                {
                                                    locale: 'id-ID',
                                                    currency: 'IDR',
                                                },
                                            )}
                                        </TableCell>
                                        <TableCell>{line.memo || '-'}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                            <TableFooter>
                                <TableRow>
                                    <TableCell className="font-semibold">
                                        Total
                                    </TableCell>
                                    <TableCell className="text-right font-semibold">
                                        {formatCurrencyByRegionalSettings(totalDebit, {
                                            locale: 'id-ID',
                                            currency: 'IDR',
                                        })}
                                    </TableCell>
                                    <TableCell className="text-right font-semibold">
                                        {formatCurrencyByRegionalSettings(totalCredit, {
                                            locale: 'id-ID',
                                            currency: 'IDR',
                                        })}
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
}
