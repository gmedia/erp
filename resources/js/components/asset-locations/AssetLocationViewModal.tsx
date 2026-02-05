'use client';

import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { type AssetLocation } from '@/types/entity';

interface AssetLocationViewModalProps {
    open: boolean;
    onClose: () => void;
    item: AssetLocation | null;
}

export function AssetLocationViewModal({ open, onClose, item }: AssetLocationViewModalProps) {
    if (!item) return null;

    return (
        <Dialog open={open} onOpenChange={onClose}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>{item.name}</DialogTitle>
                    <DialogDescription>View asset location details</DialogDescription>
                </DialogHeader>
                <div className="space-y-4">
                    <div>
                        <span className="font-semibold">Code:</span>{' '}
                        {item.code}
                    </div>
                    <div>
                        <span className="font-semibold">Branch:</span>{' '}
                        <Badge variant="outline">{item.branch?.name || 'N/A'}</Badge>
                    </div>
                    <div>
                        <span className="font-semibold">Parent Location:</span>{' '}
                        {item.parent?.name || 'None'}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
