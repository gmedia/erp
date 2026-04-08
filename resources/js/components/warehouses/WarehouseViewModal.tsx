'use client';

import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
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
        <ViewModalShell
            open={open}
            onClose={onClose}
            title={item.name}
            description="View warehouse details"
            contentClassName="max-w-md"
        >
                <div className="space-y-4 py-2">
                    <div className="space-y-1">
                        <span className="text-sm font-medium text-muted-foreground">
                            Code
                        </span>
                        <div className="text-sm font-medium break-words">
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
                </div>
        </ViewModalShell>
    );
}
