import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { type AssetMaintenance } from '@/types/asset-maintenance';
import { format } from 'date-fns';

interface AssetMaintenanceViewModalProps {
    open: boolean;
    onClose: () => void;
    item: AssetMaintenance | null;
}

export function AssetMaintenanceViewModal({ open, onClose, item }: AssetMaintenanceViewModalProps) {
    if (!item) return null;

    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent className="sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle>Maintenance Details</DialogTitle>
                </DialogHeader>

                <div className="space-y-4 py-2">
                    <div className="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span className="text-muted-foreground block text-xs">Asset</span>
                            <span className="font-medium">
                                {item.asset?.name || '-'} ({item.asset?.asset_code || '-'})
                            </span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">Type</span>
                            <span className="capitalize">{item.maintenance_type}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">Status</span>
                            <span className="capitalize">{item.status}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">Supplier</span>
                            <span>{item.supplier || '-'}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">Scheduled At</span>
                            <span>{item.scheduled_at ? format(new Date(item.scheduled_at), 'PPP') : '-'}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">Performed At</span>
                            <span>{item.performed_at ? format(new Date(item.performed_at), 'PPP') : '-'}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">Cost</span>
                            <span>
                                {new Intl.NumberFormat('id-ID', {
                                    style: 'currency',
                                    currency: 'IDR',
                                    minimumFractionDigits: 0,
                                }).format(Number(item.cost || 0))}
                            </span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">Recorded By</span>
                            <span>{item.created_by || '-'}</span>
                        </div>
                    </div>

                    {item.notes && (
                        <div>
                            <span className="text-muted-foreground block text-xs mb-1">Notes</span>
                            <p className="text-sm bg-muted/30 p-2 rounded border">{item.notes}</p>
                        </div>
                    )}
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
