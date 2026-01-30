'use client';

import { memo } from 'react';

import { ViewField } from '@/components/common/ViewField';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatDate } from '@/lib/utils';
import { useTranslation } from '@/contexts/i18n-context';
import { type ProductCategory } from './ProductCategoryColumns';

interface ProductCategoryViewModalProps {
    open: boolean;
    onClose: () => void;
    item: ProductCategory | null;
}

/**
 * ProductCategoryViewModal - A read-only modal to display product category details including description.
 */
export const ProductCategoryViewModal = memo<ProductCategoryViewModalProps>(
    function ProductCategoryViewModal({ open, onClose, item }) {
        const { t } = useTranslation();
        if (!item) return null;

        return (
            <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
                <DialogContent className="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle>View Product Category</DialogTitle>
                        <DialogDescription>
                            {t('common.view_details')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <ViewField label="Name" value={item.name} />
                        <ViewField 
                            label="Description" 
                            value={item.description || '-'} 
                            className="whitespace-pre-wrap"
                        />
                        <ViewField
                            label="Created At"
                            value={formatDate(item.created_at)}
                        />
                        <ViewField
                            label="Updated At"
                            value={formatDate(item.updated_at)}
                        />
                    </div>

                    <DialogFooter>
                        <Button type="button" onClick={onClose}>
                            Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        );
    },
);
