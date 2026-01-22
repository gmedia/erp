'use client';

import { memo } from 'react';

import { ViewField } from '@/components/common/ViewField';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatDate } from '@/lib/utils';

interface SimpleEntity {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

interface SimpleEntityViewModalProps {
    open: boolean;
    onClose: () => void;
    item: SimpleEntity | null;
    entityName: string;
}

/**
 * SimpleEntityViewModal - A read-only modal to display simple entity details.
 * Used for entities like Department and Position that only have a name field.
 */
export const SimpleEntityViewModal = memo<SimpleEntityViewModalProps>(
    function SimpleEntityViewModal({ open, onClose, item, entityName }) {
        if (!item) return null;

        return (
            <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
                <DialogContent className="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>View {entityName}</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <ViewField label="Name" value={item.name} />
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
