import { ViewField } from '@/components/common/ViewField';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
import React from 'react';

import { useTranslation } from '@/contexts/i18n-context';
import { Supplier } from '@/types/entity';

interface SupplierViewModalProps {
    open: boolean;
    onClose: () => void;
    item: Supplier | null;
}

export const SupplierViewModal = React.memo(
    ({ item, open, onClose }: SupplierViewModalProps) => {
        const { t } = useTranslation();
        if (!item) return null;

        return (
            <Dialog open={open} onOpenChange={onClose}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Supplier Details</DialogTitle>
                        <DialogDescription>
                            {t('common.view_details')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid grid-cols-1 gap-6 py-4 sm:grid-cols-2">
                        <ViewField label="Name" value={item.name} />
                        <ViewField label="Email" value={item.email} />
                        <ViewField label="Phone" value={item.phone} />
                        <ViewField label="Address" value={item.address} />

                        <ViewField label="Branch" value={item.branch?.name} />

                        <ViewField
                            label="Category"
                            value={
                                <Badge variant="outline">
                                    {item.category?.name || '-'}
                                </Badge>
                            }
                        />

                        <ViewField
                            label="Status"
                            value={
                                <Badge
                                    variant={
                                        item.status === 'active'
                                            ? 'default'
                                            : 'destructive'
                                    }
                                >
                                    {item.status === 'active'
                                        ? 'Active'
                                        : 'Inactive'}
                                </Badge>
                            }
                        />

                        <ViewField
                            label="Created At"
                            value={formatDateTimeByRegionalSettings(
                                item.created_at,
                            )}
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
