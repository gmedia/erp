'use client';

import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { type AssetLocation } from '@/types/entity';

interface AssetLocationViewModalProps {
    readonly open: boolean;
    readonly onClose: () => void;
    readonly item: AssetLocation | null;
}

export function AssetLocationViewModal({
    open,
    onClose,
    item,
}: AssetLocationViewModalProps) {
    if (!item) return null;

    return (
        <Dialog open={open} onOpenChange={onClose}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>{item.name}</DialogTitle>
                    <DialogDescription>
                        View asset location details
                    </DialogDescription>
                </DialogHeader>
                <div className="space-y-4 py-2">
                    <div className="space-y-1">
                        <span className="text-sm font-medium text-muted-foreground">
                            Code
                        </span>
                        <div className="break-words text-sm font-medium">
                            {item.code}
                        </div>
                    </div>
                    <div className="space-y-1">
                        <span className="text-sm font-medium text-muted-foreground">
                            Branch
                        </span>
                        <div>
                            <Badge variant="outline">
                                {item.branch?.name || 'N/A'}
                            </Badge>
                        </div>
                    </div>
                    <div className="space-y-1">
                        <span className="text-sm font-medium text-muted-foreground">
                            Parent Location
                        </span>
                        <div className="break-words text-sm font-medium">
                            {item.parent?.name || 'None'}
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
