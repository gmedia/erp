'use client';

import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { type AssetModel } from '@/types/entity';

interface AssetModelViewModalProps {
    open: boolean;
    onClose: () => void;
    item: AssetModel | null;
}

export function AssetModelViewModal({ open, onClose, item }: AssetModelViewModalProps) {
    if (!item) return null;

    return (
        <Dialog open={open} onOpenChange={onClose}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>{item.model_name}</DialogTitle>
                    <DialogDescription>View asset model details</DialogDescription>
                </DialogHeader>
                <div className="space-y-4">
                    <div>
                        <span className="font-semibold">Manufacturer:</span>{' '}
                        {item.manufacturer || 'N/A'}
                    </div>
                    <div>
                        <span className="font-semibold">Category:</span>{' '}
                        <Badge variant="outline">{item.category?.name || 'N/A'}</Badge>
                    </div>
                    {item.specs && (
                        <div>
                            <span className="font-semibold">Specifications:</span>
                            <pre className="mt-1 rounded bg-muted p-2 text-xs">
                                {JSON.stringify(item.specs, null, 2)}
                            </pre>
                        </div>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
