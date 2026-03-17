import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { JournalEntry } from '@/types/journal-entry';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';

interface JournalEntryViewModalProps {
    item: JournalEntry | null;
    open: boolean;
    onClose: () => void;
}

function getJournalStatusVariant(status: JournalEntry['status']) {
    if (status === 'posted') {
        return 'default';
    }
    if (status === 'draft') {
        return 'secondary';
    }

    return 'destructive';
}

export function JournalEntryViewModal({
    item,
    open,
    onClose,
}: Readonly<JournalEntryViewModalProps>) {
    if (!item) return null;

    return (
        <Dialog open={open} onOpenChange={onClose}>
            <DialogContent className="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-7xl">
                <DialogHeader>
                    <DialogTitle>Journal Entry Details</DialogTitle>
                    <DialogDescription>
                        View complete details of this journal entry
                    </DialogDescription>
                </DialogHeader>

                <ScrollArea className="flex-1 pr-4">
                    <div className="space-y-6 py-2">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <p className="text-sm font-medium text-gray-500">
                                    Entry Number
                                </p>
                                <p>{item.entry_number}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-500">
                                    Date
                                </p>
                                <p>
                                    {formatDateByRegionalSettings(item.entry_date)}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-500">
                                    Reference
                                </p>
                                <p>{item.reference || '-'}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-500">
                                    Status
                                </p>
                                <Badge
                                    variant={getJournalStatusVariant(
                                        item.status,
                                    )}
                                >
                                    {item.status.toUpperCase()}
                                </Badge>
                            </div>
                            <div className="col-span-2">
                                <p className="text-sm font-medium text-gray-500">
                                    Description
                                </p>
                                <p>{item.description}</p>
                            </div>
                        </div>

                        <div className="overflow-x-auto max-w-[calc(100vw-3rem)] sm:max-w-none rounded-md border min-w-0">
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
                                            <TableCell>{line.memo}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                                <TableFooter>
                                    <TableRow>
                                        <TableCell className="font-bold">
                                            Total
                                        </TableCell>
                                        <TableCell className="text-right font-bold">
                                            {formatCurrencyByRegionalSettings(
                                                item.total_debit,
                                                {
                                                    locale: 'id-ID',
                                                    currency: 'IDR',
                                                },
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right font-bold">
                                            {formatCurrencyByRegionalSettings(
                                                item.total_credit,
                                                {
                                                    locale: 'id-ID',
                                                    currency: 'IDR',
                                                },
                                            )}
                                        </TableCell>
                                        <TableCell></TableCell>
                                    </TableRow>
                                </TableFooter>
                            </Table>
                        </div>
                    </div>
                </ScrollArea>

                <DialogFooter>
                    <Button type="button" onClick={onClose}>
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
