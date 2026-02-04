'use client';

import * as React from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { AssetCategory } from '@/types/asset-category';
import { formatDate } from '@/lib/utils';

interface AssetCategoryViewModalProps {
    open: boolean;
    onClose: () => void;
    item: AssetCategory | null;
}

export function AssetCategoryViewModal({
    open,
    onClose,
    item,
}: AssetCategoryViewModalProps) {
    if (!item) return null;

    return (
        <Dialog open={open} onOpenChange={(val) => !val && onClose()}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>Asset Category Details</DialogTitle>
                </DialogHeader>
                <div className="grid gap-4 py-4">
                    <div className="grid grid-cols-4 items-center gap-4">
                        <span className="font-bold">Code:</span>
                        <span className="col-span-3">{item.code}</span>
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <span className="font-bold">Name:</span>
                        <span className="col-span-3">{item.name}</span>
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <span className="font-bold">Useful Life:</span>
                        <span className="col-span-3">{item.useful_life_months_default} months</span>
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <span className="font-bold">Created At:</span>
                        <span className="col-span-3">{formatDate(item.created_at)}</span>
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <span className="font-bold">Updated At:</span>
                        <span className="col-span-3">{formatDate(item.updated_at)}</span>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
