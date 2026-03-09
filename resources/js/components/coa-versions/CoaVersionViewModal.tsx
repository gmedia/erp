'use client';

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
import { useTranslation } from '@/contexts/i18n-context';
import { formatDate } from '@/lib/utils';
import { type CoaVersion } from '@/types/coa-version';
import { memo } from 'react';

interface CoaVersionViewModalProps {
    open: boolean;
    onClose: () => void;
    item: CoaVersion | null;
}

export const CoaVersionViewModal = memo<CoaVersionViewModalProps>(
    function CoaVersionViewModal({ open, onClose, item }) {
        const { t } = useTranslation();
        if (!item) return null;

        return (
            <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
                <DialogContent className="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>View COA Version</DialogTitle>
                        <DialogDescription>
                            {t('common.view_details')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <ViewField label="Name" value={item.name} />
                        <ViewField
                            label="Fiscal Year"
                            value={item.fiscal_year?.name || '-'}
                        />
                        <ViewField
                            label="Status"
                            value={item.status.toUpperCase()}
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
