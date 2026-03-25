'use client';

import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { type AssetModel } from '@/types/entity';

interface AssetModelViewModalProps {
    readonly open: boolean;
    readonly onClose: () => void;
    readonly item: AssetModel | null;
}

export function AssetModelViewModal({
    open,
    onClose,
    item,
}: AssetModelViewModalProps) {
    if (!item) return null;

    return (
        <Dialog open={open} onOpenChange={onClose}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>{item.model_name}</DialogTitle>
                    <DialogDescription>
                        View asset model details
                    </DialogDescription>
                </DialogHeader>
                <div className="space-y-4 py-2">
                    <div className="space-y-1">
                        <span className="text-sm font-medium text-muted-foreground">
                            Manufacturer
                        </span>
                        <div className="text-sm font-medium break-words">
                            {item.manufacturer || 'N/A'}
                        </div>
                    </div>
                    <div className="space-y-1">
                        <span className="text-sm font-medium text-muted-foreground">
                            Category
                        </span>
                        <div>
                            <Badge variant="outline">
                                {item.category?.name || 'N/A'}
                            </Badge>
                        </div>
                    </div>
                    {item.specs && (
                        <div className="space-y-1">
                            <span className="text-sm font-medium text-muted-foreground">
                                Specifications:
                            </span>
                            <pre className="overflow-x-auto rounded bg-muted p-2 text-xs break-words whitespace-pre-wrap">
                                {JSON.stringify(item.specs, null, 2)}
                            </pre>
                        </div>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
