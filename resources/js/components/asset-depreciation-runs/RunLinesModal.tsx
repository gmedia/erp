import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import axios from '@/lib/axios';
import { AssetDepreciationLine } from '@/types/asset-depreciation-run';
import { useQuery } from '@tanstack/react-query';
import { Loader2 } from 'lucide-react';

interface RunLinesModalProps {
    runId: number | null;
    open: boolean;
    onClose: () => void;
}

export function RunLinesModal({ runId, open, onClose }: Readonly<RunLinesModalProps>) {
    const { data: lines, isLoading } = useQuery<AssetDepreciationLine[]>({
        queryKey: ['asset-depreciation-run-lines', runId],
        queryFn: async () => {
            const { data } = await axios.get(
                `/api/asset-depreciation-runs/${runId}/lines`,
            );
            return data.data; // Assumes ResourceCollection format { data: [...] }
        },
        enabled: !!runId && open,
    });

    const formatCurrency = (val: Readonly<number>) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
        }).format(val);
    };

    return (
        <Dialog open={open} onOpenChange={(val) => !val && onClose()}>
            <DialogContent className="flex max-h-[90vh] max-w-[95vw] flex-col overflow-hidden sm:max-w-7xl">
                <DialogHeader>
                    <DialogTitle>Depreciation Run Lines</DialogTitle>
                    <DialogDescription>
                        View detailed depreciation line items for the selected
                        run.
                    </DialogDescription>
                </DialogHeader>

                <ScrollArea className="flex-1 rounded-md border">
                    <Table>
                        <TableHeader className="sticky top-0 bg-muted">
                            <TableRow>
                                <TableHead>Asset</TableHead>
                                <TableHead className="text-right">
                                    Amount
                                </TableHead>
                                <TableHead className="text-right">
                                    Accumulated Before
                                </TableHead>
                                <TableHead className="text-right">
                                    Accumulated After
                                </TableHead>
                                <TableHead className="text-right">
                                    Book Value After
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {isLoading ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="h-40 text-center"
                                    >
                                        <div className="flex items-center justify-center gap-2">
                                            <Loader2 className="h-6 w-6 animate-spin text-primary" />
                                            <span className="text-muted-foreground">
                                                Loading lines...
                                            </span>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ) : !lines || lines.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="h-40 text-center text-muted-foreground"
                                    >
                                        No lines found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                lines.map((line) => (
                                    <TableRow key={line.id}>
                                        <TableCell>
                                            <div className="font-medium">
                                                {line.asset?.name}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {line.asset?.asset_code}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatCurrency(line.amount)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatCurrency(
                                                line.accumulated_before,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatCurrency(
                                                line.accumulated_after,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatCurrency(
                                                line.book_value_after,
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </ScrollArea>
            </DialogContent>
        </Dialog>
    );
}
