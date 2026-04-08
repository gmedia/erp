'use client';

import { ViewModalShell } from '@/components/common/ViewModalShell';
import { formatDate } from '@/lib/utils';
import { AssetCategory } from '@/types/asset-category';

interface AssetCategoryViewModalProps {
    open: boolean;
    onClose: () => void;
    item: AssetCategory | null;
}

export function AssetCategoryViewModal({
    open,
    onClose,
    item,
}: Readonly<AssetCategoryViewModalProps>) {
    if (!item) return null;

    return (
        <ViewModalShell
            open={open}
            onClose={onClose}
            title="Asset Category Details"
            description="View complete details for the selected asset category."
            contentClassName="sm:max-w-[500px]"
        >
            <div className="grid gap-4 py-4">
                <div className="grid grid-cols-1 gap-1 sm:grid-cols-4 sm:items-center sm:gap-4">
                    <span className="font-bold">Code:</span>
                    <span className="sm:col-span-3">{item.code}</span>
                </div>
                <div className="grid grid-cols-1 gap-1 sm:grid-cols-4 sm:items-center sm:gap-4">
                    <span className="font-bold">Name:</span>
                    <span className="sm:col-span-3">{item.name}</span>
                </div>
                <div className="grid grid-cols-1 gap-1 sm:grid-cols-4 sm:items-center sm:gap-4">
                    <span className="font-bold">Useful Life:</span>
                    <span className="sm:col-span-3">
                        {item.useful_life_months_default} months
                    </span>
                </div>
                <div className="grid grid-cols-1 gap-1 sm:grid-cols-4 sm:items-center sm:gap-4">
                    <span className="font-bold">Created At:</span>
                    <span className="sm:col-span-3">
                        {formatDate(item.created_at)}
                    </span>
                </div>
                <div className="grid grid-cols-1 gap-1 sm:grid-cols-4 sm:items-center sm:gap-4">
                    <span className="font-bold">Updated At:</span>
                    <span className="sm:col-span-3">
                        {formatDate(item.updated_at)}
                    </span>
                </div>
            </div>
        </ViewModalShell>
    );
}
