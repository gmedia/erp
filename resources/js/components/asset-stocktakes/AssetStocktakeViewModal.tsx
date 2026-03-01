import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatDate } from '@/lib/utils';
import { AssetStocktake } from '@/types/asset-stocktake';

interface AssetStocktakeViewModalProps {
    open: boolean;
    onClose: () => void;
    item: AssetStocktake | null;
}

export function AssetStocktakeViewModal({ open, onClose, item }: AssetStocktakeViewModalProps) {
    if (!item) return null;

    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent className="sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle>Stocktake Details</DialogTitle>
                    <DialogDescription>
                        View complete stocktake information for the selected record.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4 py-2">
                    <div className="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span className="text-muted-foreground block text-xs">Reference</span>
                            <span className="font-medium">{item.reference}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">Branch</span>
                            <span className="font-medium">{item.branch?.name || '-'}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">Planned Date</span>
                            <span>{formatDate(item.planned_at)}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">Performed Date</span>
                            <span>{item.performed_at ? formatDate(item.performed_at) : '-'}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">Status</span>
                            <span className="capitalize">{item.status}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">Created By</span>
                            <span>{item.created_by?.name || '-'}</span>
                        </div>
                    </div>
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
