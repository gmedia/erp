'use client';

import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
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
        <ViewModalShell
            open={open}
            onClose={onClose}
            title={item.model_name}
            description="View asset model details"
            contentClassName="max-w-md"
        >
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
        </ViewModalShell>
    );
}
