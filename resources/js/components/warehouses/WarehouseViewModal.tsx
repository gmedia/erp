'use client';

import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { type Warehouse } from '@/types/entity';

interface WarehouseViewModalProps {
    readonly open: boolean;
    readonly onClose: () => void;
    readonly item: Warehouse | null;
}

export function WarehouseViewModal({
    open,
    onClose,
    item,
}: WarehouseViewModalProps) {
    if (!item) return null;

    return (
        <Dialog open={open} onOpenChange={onClose}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>{item.name}</DialogTitle>
                    <DialogDescription>
                        View warehouse details
                    </DialogDescription>
                </DialogHeader>
                <div className="space-y-4">
                    <div>
                        <span className="font-semibold">Code:</span> {item.code}
                    </div>
                    <div>
                        <span className="font-semibold">Branch:</span>{' '}
                        <Badge variant="outline">
                            {item.branch?.name || 'N/A'}
                        </Badge>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
