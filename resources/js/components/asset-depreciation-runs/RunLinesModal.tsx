import {
    Dialog,
    DialogContent,
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
} from '@/components/ui/table';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Loader2 } from 'lucide-react';
import { AssetDepreciationLine } from '@/types/asset-depreciation-run';

interface RunLinesModalProps {
    runId: number | null;
    open: boolean;
    onClose: () => void;
}

export function RunLinesModal({ runId, open, onClose }: RunLinesModalProps) {
    const { data: lines, isLoading } = useQuery<AssetDepreciationLine[]>({
        queryKey: ['asset-depreciation-run-lines', runId],
        queryFn: async () => {
            const { data } = await axios.get(`/api/asset-depreciation-runs/${runId}/lines`);
            return data.data; // Assumes ResourceCollection format { data: [...] }
        },
        enabled: !!runId && open,
    });

    const formatCurrency = (val: number) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val);
    };

    return (
        <Dialog open={open} onOpenChange={(val) => !val && onClose()}>
            <DialogContent className="max-w-[95vw] sm:max-w-7xl max-h-[90vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle>Depreciation Run Lines</DialogTitle>
                </DialogHeader>

                <div className="flex-1 overflow-auto border rounded-md">
                    <Table>
                        <TableHeader className="bg-muted sticky top-0">
                            <TableRow>
                                <TableHead>Asset</TableHead>
                                <TableHead className="text-right">Amount</TableHead>
                                <TableHead className="text-right">Accumulated Before</TableHead>
                                <TableHead className="text-right">Accumulated After</TableHead>
                                <TableHead className="text-right">Book Value After</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {isLoading ? (
                                <TableRow>
                                    <TableCell colSpan={5} className="h-40 text-center">
                                        <div className="flex justify-center items-center gap-2">
                                            <Loader2 className="h-6 w-6 animate-spin text-primary" />
                                            <span className="text-muted-foreground">Loading lines...</span>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ) : !lines || lines.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={5} className="h-40 text-center text-muted-foreground">
                                        No lines found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                lines.map((line) => (
                                    <TableRow key={line.id}>
                                        <TableCell>
                                            <div className="font-medium">{line.asset?.name}</div>
                                            <div className="text-xs text-muted-foreground">{line.asset?.asset_code}</div>
                                        </TableCell>
                                        <TableCell className="text-right font-mono">{formatCurrency(line.amount)}</TableCell>
                                        <TableCell className="text-right font-mono">{formatCurrency(line.accumulated_before)}</TableCell>
                                        <TableCell className="text-right font-mono">{formatCurrency(line.accumulated_after)}</TableCell>
                                        <TableCell className="text-right font-mono">{formatCurrency(line.book_value_after)}</TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </DialogContent>
        </Dialog>
    );
}
