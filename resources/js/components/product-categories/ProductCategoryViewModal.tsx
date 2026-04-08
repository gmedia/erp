'use client';

import { memo } from 'react';

import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { useTranslation } from '@/contexts/i18n-context';
import { formatDate } from '@/lib/utils';
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
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="View Product Category"
                description={t('common.view_details')}
                contentClassName="sm:max-w-[500px]"
            >
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
            </ViewModalShell>
        );
    },
);
