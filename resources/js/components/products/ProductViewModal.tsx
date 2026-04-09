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

const BooleanBadge = ({ value }: { value: boolean }) => (
    <Badge variant={value ? 'default' : 'secondary'}>
        {value ? 'Yes' : 'No'}
    </Badge>
);

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
                        {/* General Info */}
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

                        {/* Pricing */}
                        <div className="space-y-4 border-b pb-4 md:col-span-2">
                            <h3 className="text-sm font-semibold tracking-wider text-muted-foreground uppercase">
                                Pricing
                            </h3>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <ViewField
                                    label="Cost"
                                    value={formatRupiah(item.cost)}
                                />
                                <ViewField
                                    label="Selling Price"
                                    value={formatRupiah(item.selling_price)}
                                />
                                <ViewField
                                    label="Markup"
                                    value={`${item.markup_percentage || '0'}%`}
                                />
                            </div>
                        </div>

                        {/* Configuration */}
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
                                <ViewField
                                    label="Trial Period"
                                    value={`${item.trial_period_days || 0} Days`}
                                />
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:col-span-2 md:grid-cols-3">
                                    <ViewField
                                        label="Recurring"
                                        value={
                                            <BooleanBadge
                                                value={item.is_recurring}
                                            />
                                        }
                                    />
                                    <ViewField
                                        label="One-Time Purchase"
                                        value={
                                            <BooleanBadge
                                                value={
                                                    item.allow_one_time_purchase
                                                }
                                            />
                                        }
                                    />
                                    <ViewField
                                        label="Manufactured"
                                        value={
                                            <BooleanBadge
                                                value={item.is_manufactured}
                                            />
                                        }
                                    />
                                    <ViewField
                                        label="Purchasable"
                                        value={
                                            <BooleanBadge
                                                value={item.is_purchasable}
                                            />
                                        }
                                    />
                                    <ViewField
                                        label="Sellable"
                                        value={
                                            <BooleanBadge
                                                value={item.is_sellable}
                                            />
                                        }
                                    />
                                    <ViewField
                                        label="Taxable"
                                        value={
                                            <BooleanBadge
                                                value={item.is_taxable}
                                            />
                                        }
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Metadata */}
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
