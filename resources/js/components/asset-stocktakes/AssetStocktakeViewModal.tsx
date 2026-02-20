import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { AssetStocktake } from '@/types/asset-stocktake';

interface AssetStocktakeViewModalProps {
    open: boolean;
    onClose: () => void;
    item: AssetStocktake | null;
}

export function AssetStocktakeViewModal({ open, onClose, item }: AssetStocktakeViewModalProps) {
    if (!item) return null;

    return (
        <Dialog open={open} onOpenChange={onClose}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{item.reference}</DialogTitle>
                </DialogHeader>
                <div className="space-y-4">
                    <div>
                        <span className="font-semibold">Branch:</span> {item.branch.name}
                    </div>
                    <div>
                        <span className="font-semibold">Planned Date:</span> {new Date(item.planned_at).toLocaleDateString()}
                    </div>
                    <div>
                        <span className="font-semibold">Performed Date:</span> {item.performed_at ? new Date(item.performed_at).toLocaleDateString() : '-'}
                    </div>
                    <div>
                        <span className="font-semibold">Status:</span> {item.status}
                    </div>
                    <div>
                        <span className="font-semibold">Created By:</span> {item.created_by?.name || '-'}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
