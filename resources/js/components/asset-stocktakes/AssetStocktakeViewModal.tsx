import { ViewModalShell } from '@/components/common/ViewModalShell';
import { formatDate } from '@/lib/utils';
import { AssetStocktake } from '@/types/asset-stocktake';

interface AssetStocktakeViewModalProps {
    open: boolean;
    onClose: () => void;
    item: AssetStocktake | null;
}

export function AssetStocktakeViewModal({
    open,
    onClose,
    item,
}: Readonly<AssetStocktakeViewModalProps>) {
    if (!item) return null;

    return (
        <ViewModalShell
            open={open}
            onClose={onClose}
            title="Stocktake Details"
            description="View complete stocktake information for the selected record."
            contentClassName="sm:max-w-[600px]"
        >
            <div className="space-y-4 py-2">
                <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <span className="block text-xs text-muted-foreground">
                            Reference
                        </span>
                        <span className="font-medium">{item.reference}</span>
                    </div>
                    <div>
                        <span className="block text-xs text-muted-foreground">
                            Branch
                        </span>
                        <span className="font-medium">
                            {item.branch?.name || '-'}
                        </span>
                    </div>
                    <div>
                        <span className="block text-xs text-muted-foreground">
                            Planned Date
                        </span>
                        <span>{formatDate(item.planned_at)}</span>
                    </div>
                    <div>
                        <span className="block text-xs text-muted-foreground">
                            Performed Date
                        </span>
                        <span>
                            {item.performed_at
                                ? formatDate(item.performed_at)
                                : '-'}
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
                            Created By
                        </span>
                        <span>{item.created_by?.name || '-'}</span>
                    </div>
                </div>
            </div>
        </ViewModalShell>
    );
}
