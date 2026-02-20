import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
    TableFooter,
} from '@/components/ui/table';
import { JournalEntry } from '@/types/journal-entry';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { format } from 'date-fns';

interface JournalEntryViewModalProps {
    item: JournalEntry | null;
    open: boolean;
    onClose: () => void;
}

export function JournalEntryViewModal({
    item,
    open,
    onClose,
}: JournalEntryViewModalProps) {
    if (!item) return null;

    return (
        <Dialog open={open} onOpenChange={onClose}>
            <DialogContent className="max-w-[95vw] sm:max-w-7xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Journal Entry Details</DialogTitle>
                    <DialogDescription>
                        View complete details of this journal entry
                    </DialogDescription>
                </DialogHeader>

                <div className="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <p className="text-sm font-medium text-gray-500">Entry Number</p>
                        <p>{item.entry_number}</p>
                    </div>
                    <div>
                        <p className="text-sm font-medium text-gray-500">Date</p>
                        <p>{format(new Date(item.entry_date), 'dd MMMM yyyy')}</p>
                    </div>
                    <div>
                        <p className="text-sm font-medium text-gray-500">Reference</p>
                        <p>{item.reference || '-'}</p>
                    </div>
                    <div>
                        <p className="text-sm font-medium text-gray-500">Status</p>
                        <Badge
                             variant={
                                item.status === 'posted'
                                    ? 'default'
                                    : item.status === 'draft'
                                      ? 'secondary'
                                      : 'destructive'
                            }
                        >
                            {item.status.toUpperCase()}
                        </Badge>
                    </div>
                    <div className="col-span-2">
                        <p className="text-sm font-medium text-gray-500">Description</p>
                        <p>{item.description}</p>
                    </div>
                </div>

                <div className="border rounded-md overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Account</TableHead>
                                <TableHead className="text-right">Debit</TableHead>
                                <TableHead className="text-right">Credit</TableHead>
                                <TableHead>Memo</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {item.lines.map((line) => (
                                <TableRow key={line.id}>
                                    <TableCell>
                                        <div>{line.account_code}</div>
                                        <div className="text-sm text-gray-500">{line.account_name}</div>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(line.debit)}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(line.credit)}
                                    </TableCell>
                                    <TableCell>{line.memo}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                        <TableFooter>
                            <TableRow>
                                <TableCell className="font-bold">Total</TableCell>
                                <TableCell className="text-right font-bold">
                                     {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(item.total_debit)}
                                </TableCell>
                                <TableCell className="text-right font-bold">
                                     {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(item.total_credit)}
                                </TableCell>
                                <TableCell></TableCell>
                            </TableRow>
                        </TableFooter>
                    </Table>
                </div>
                <DialogFooter>
                    <Button type="button" onClick={onClose}>
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
