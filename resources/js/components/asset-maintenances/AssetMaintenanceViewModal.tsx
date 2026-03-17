import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { type AssetMaintenance } from '@/types/asset-maintenance';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';

interface AssetMaintenanceViewModalProps {
    open: boolean;
    onClose: () => void;
    item: AssetMaintenance | null;
}

export function AssetMaintenanceViewModal({
    open,
    onClose,
    item,
}: Readonly<AssetMaintenanceViewModalProps>) {
    if (!item) return null;

    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent className="sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle>Maintenance Details</DialogTitle>
                    <DialogDescription>
                        View complete maintenance information for the selected
                        asset.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4 py-2">
                    <div className="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span className="block text-xs text-muted-foreground">
                                Asset
                            </span>
                            <span className="font-medium">
                                {item.asset?.name || '-'} (
                                {item.asset?.asset_code || '-'})
                            </span>
                        </div>
                        <div>
                            <span className="block text-xs text-muted-foreground">
                                Type
                            </span>
                            <span className="capitalize">
                                {item.maintenance_type}
                            </span>
                        </div>
                        <div>
                            <span className="block text-xs text-muted-foreground">
                                Status
                            </span>
                            <span className="capitalize">{item.status}</span>
                        </div>
                        <div>
                            <span className="block text-xs text-muted-foreground">
                                Supplier
                            </span>
                            <span>{item.supplier || '-'}</span>
                        </div>
                        <div>
                            <span className="block text-xs text-muted-foreground">
                                Scheduled At
                            </span>
                            <span>
                                {formatDateByRegionalSettings(item.scheduled_at)}
                            </span>
                        </div>
                        <div>
                            <span className="block text-xs text-muted-foreground">
                                Performed At
                            </span>
                            <span>
                                {formatDateByRegionalSettings(item.performed_at)}
                            </span>
                        </div>
                        <div>
                            <span className="block text-xs text-muted-foreground">
                                Cost
                            </span>
                            <span>
                                {formatCurrencyByRegionalSettings(
                                    Number(item.cost || 0),
                                    {
                                        locale: 'id-ID',
                                        currency: 'IDR',
                                        minimumFractionDigits: 0,
                                    },
                                )}
                            </span>
                        </div>
                        <div>
                            <span className="block text-xs text-muted-foreground">
                                Recorded By
                            </span>
                            <span>{item.created_by || '-'}</span>
                        </div>
                    </div>

                    {item.notes && (
                        <div>
                            <span className="mb-1 block text-xs text-muted-foreground">
                                Notes
                            </span>
                            <p className="rounded border bg-muted/30 p-2 text-sm">
                                {item.notes}
                            </p>
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
