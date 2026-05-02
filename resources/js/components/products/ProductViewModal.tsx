'use client';

import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/contexts/i18n-context';
import { Product } from '@/types/entity';
import { formatRupiah } from '@/utils/formatters';
import { memo } from 'react';

interface ProductViewModalProps {
    open: boolean;
    onClose: () => void;
    item: Product | null;
}

export const ProductViewModal = memo<ProductViewModalProps>(
    function ProductViewModal({ open, onClose, item }) {
        const { t } = useTranslation();
        if (!item) return null;

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="View Product/Service"
                description={t('common.view_details')}
                contentClassName="flex max-h-[90vh] flex-col overflow-hidden p-0 sm:max-w-[600px]"
                headerClassName="shrink-0 p-6 pb-2"
                footerClassName="shrink-0 p-6 pt-2"
            >
                <div className="min-h-0 flex-1 overflow-y-auto px-6">
                    <div className="grid grid-cols-1 gap-x-6 gap-y-4 py-4 md:grid-cols-2">
                        <div className="space-y-4 border-b pb-4 md:col-span-2">
                            <h3 className="text-sm font-semibold tracking-wider text-muted-foreground uppercase">
                                General Information
                            </h3>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <ViewField label="Code" value={item.code} />
                                <ViewField label="Name" value={item.name} />
                                <div className="col-span-2">
                                    <ViewField
                                        label="Description"
                                        value={item.description}
                                    />
                                </div>
                                <ViewField
                                    label="Category"
                                    value={item.category.name}
                                />
                                <ViewField
                                    label="Unit"
                                    value={`${item.unit.name} (${item.unit.symbol || '-'})`}
                                />
                                <ViewField
                                    label="Branch"
                                    value={item.branch?.name || '-'}
                                />
                                <ViewField
                                    label="Type"
                                    value={
                                        <Badge variant="outline">
                                            {item.type}
                                        </Badge>
                                    }
                                />
                            </div>
                        </div>

                        <div className="space-y-4 border-b pb-4 md:col-span-2">
                            <h3 className="text-sm font-semibold tracking-wider text-muted-foreground uppercase">
                                Pricing
                            </h3>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <ViewField
                                    label="Cost"
                                    value={formatRupiah(item.cost)}
                                />
                                <ViewField
                                    label="Selling Price"
                                    value={formatRupiah(item.selling_price)}
                                />
                            </div>
                        </div>

                        <div className="space-y-4 border-b pb-4 md:col-span-2">
                            <h3 className="text-sm font-semibold tracking-wider text-muted-foreground uppercase">
                                Configuration
                            </h3>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <ViewField
                                    label="Billing Model"
                                    value={
                                        <Badge variant="secondary">
                                            {item.billing_model}
                                        </Badge>
                                    }
                                />
                            </div>
                        </div>

                        <div className="space-y-4 md:col-span-2">
                            <h3 className="text-sm font-semibold tracking-wider text-muted-foreground uppercase">
                                Status & Notes
                            </h3>
                            <ViewField
                                label="Status"
                                value={
                                    <Badge
                                        variant={
                                            item.status === 'active'
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {item.status}
                                    </Badge>
                                }
                            />
                            <ViewField label="Notes" value={item.notes} />
                        </div>
                    </div>
                </div>
            </ViewModalShell>
        );
    },
);
