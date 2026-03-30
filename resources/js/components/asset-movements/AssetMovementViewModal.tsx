import { type AssetMovement } from '@/components/asset-movements/AssetMovementColumns';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';

interface AssetMovementViewModalProps {
    open: boolean;
    onClose: () => void;
    item: AssetMovement | null;
}

export function AssetMovementViewModal({
    open,
    onClose,
    item,
}: Readonly<AssetMovementViewModalProps>) {
    if (!item) return null;

    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent className="sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle>Movement Details</DialogTitle>
                    <DialogDescription>
                        View detailed information for this asset movement.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4 py-2">
                    <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
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
                                {item.movement_type}
                            </span>
                        </div>
                        <div>
                            <span className="block text-xs text-muted-foreground">
                                Date
                            </span>
                            <span>
                                {formatDateTimeByRegionalSettings(
                                    item.moved_at,
                                )}
                            </span>
                        </div>
                        <div>
                            <span className="block text-xs text-muted-foreground">
                                Reference
                            </span>
                            <span>{item.reference || '-'}</span>
                        </div>
                    </div>

                    <hr />

                    <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                        <div className="space-y-2">
                            <span className="block text-xs font-medium text-muted-foreground">
                                Origin
                            </span>
                            <div className="rounded bg-muted/50 p-2">
                                {item.from_branch && (
                                    <div>{item.from_branch}</div>
                                )}
                                {item.from_location && (
                                    <div className="text-xs text-muted-foreground">
                                        {item.from_location}
                                    </div>
                                )}
                                {item.from_employee && (
                                    <div className="text-xs text-primary">
                                        {item.from_employee}
                                    </div>
                                )}
                                {!item.from_branch && !item.from_employee && (
                                    <span className="text-xs text-muted-foreground italic">
                                        Initial/Acquired
                                    </span>
                                )}
                            </div>
                        </div>
                        <div className="space-y-2">
                            <span className="block text-xs font-medium text-muted-foreground">
                                Destination
                            </span>
                            <div className="rounded bg-primary/5 p-2">
                                {item.to_branch && <div>{item.to_branch}</div>}
                                {item.to_location && (
                                    <div className="text-xs text-muted-foreground">
                                        {item.to_location}
                                    </div>
                                )}
                                {item.to_employee && (
                                    <div className="text-xs text-primary">
                                        {item.to_employee}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {item.notes && (
                        <div className="mt-4">
                            <span className="mb-1 block text-xs text-muted-foreground">
                                Notes
                            </span>
                            <p className="rounded border bg-muted/30 p-2 text-sm">
                                {item.notes}
                            </p>
                        </div>
                    )}

                    <div className="mt-4 flex justify-between text-xs text-muted-foreground">
                        <span>Recorded by: {item.created_by}</span>
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
