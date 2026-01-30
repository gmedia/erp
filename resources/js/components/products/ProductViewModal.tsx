'use client';

import { memo } from 'react';
import { ViewField } from '@/components/common/ViewField';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatCurrency } from '@/utils/formatters';
import { Product } from '@/types/entity';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useTranslation } from '@/contexts/i18n-context';

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
            <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
                <DialogContent className="sm:max-w-[600px] max-h-[90vh] p-0 overflow-hidden flex flex-col">
                    <div className="shrink-0 p-6 pb-2">
                        <DialogHeader>
                            <DialogTitle>View Product/Service</DialogTitle>
                            <DialogDescription>
                                {t('common.view_details')}
                            </DialogDescription>
                        </DialogHeader>
                    </div>

                    <div className="flex-1 overflow-y-auto px-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 py-4 pr-6">
                        {/* Content... */}
                        {/* Note: I'm keeping the internal structure but ensuring max-h is on ScrollArea */}
                        {/* General Info */}
                        <div className="md:col-span-2 space-y-4 border-b pb-4">
                            <h3 className="font-semibold text-sm uppercase tracking-wider text-muted-foreground">General Information</h3>
                            <div className="grid grid-cols-2 gap-4">
                                <ViewField label="Code" value={item.code} />
                                <ViewField label="Name" value={item.name} />
                                <div className="col-span-2">
                                    <ViewField label="Description" value={item.description} />
                                </div>
                                <ViewField label="Category" value={item.category.name} />
                                <ViewField label="Unit" value={`${item.unit.name} (${item.unit.symbol || '-'})`} />
                                <ViewField label="Branch" value={item.branch?.name || '-'} />
                                <ViewField label="Type" value={<Badge variant="outline">{item.type}</Badge>} />
                            </div>
                        </div>

                        {/* Pricing */}
                        <div className="md:col-span-2 space-y-4 border-b pb-4">
                            <h3 className="font-semibold text-sm uppercase tracking-wider text-muted-foreground">Pricing</h3>
                            <div className="grid grid-cols-3 gap-4">
                                <ViewField label="Cost" value={formatCurrency(item.cost)} />
                                <ViewField label="Selling Price" value={formatCurrency(item.selling_price)} />
                                <ViewField label="Markup" value={`${item.markup_percentage || '0'}%`} />
                            </div>
                        </div>

                        {/* Configuration */}
                        <div className="md:col-span-2 space-y-4 border-b pb-4">
                            <h3 className="font-semibold text-sm uppercase tracking-wider text-muted-foreground">Configuration</h3>
                            <div className="grid grid-cols-2 gap-4">
                                <ViewField label="Billing Model" value={<Badge variant="secondary">{item.billing_model}</Badge>} />
                                <ViewField label="Trial Period" value={`${item.trial_period_days || 0} Days`} />
                                <div className="col-span-2 grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <ViewField label="Recurring" value={<BooleanBadge value={item.is_recurring} />} />
                                    <ViewField label="One-Time Purchase" value={<BooleanBadge value={item.allow_one_time_purchase} />} />
                                    <ViewField label="Manufactured" value={<BooleanBadge value={item.is_manufactured} />} />
                                    <ViewField label="Purchasable" value={<BooleanBadge value={item.is_purchasable} />} />
                                    <ViewField label="Sellable" value={<BooleanBadge value={item.is_sellable} />} />
                                    <ViewField label="Taxable" value={<BooleanBadge value={item.is_taxable} />} />
                                </div>
                            </div>
                        </div>

                        {/* Metadata */}
                        <div className="md:col-span-2 space-y-4">
                            <h3 className="font-semibold text-sm uppercase tracking-wider text-muted-foreground">Status & Notes</h3>
                            <ViewField label="Status" value={
                                <Badge variant={item.status === 'active' ? 'default' : 'secondary'}>
                                    {item.status}
                                </Badge>
                            } />
                            <ViewField label="Notes" value={item.notes} />
                        </div>
                    </div>
                    </div>
                    <div className="shrink-0 p-6 pt-2">
                        <DialogFooter>
                            <Button type="button" onClick={onClose}>
                                Close
                            </Button>
                        </DialogFooter>
                    </div>
                </DialogContent>
            </Dialog>
        );
    },
);
