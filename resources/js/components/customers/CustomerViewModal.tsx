'use client';

import { memo } from 'react';

import { ViewField } from '@/components/common/ViewField';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

import { Customer } from '@/types/entity';

interface CustomerViewModalProps {
    open: boolean;
    onClose: () => void;
    item: Customer | null;
}

/**
 * CustomerViewModal - A read-only modal to display customer details.
 * Similar layout to Edit modal but with static text instead of form inputs.
 */
export const CustomerViewModal = memo<CustomerViewModalProps>(
    function CustomerViewModal({ open, onClose, item }) {
        if (!item) return null;

        const branchName =
            typeof item.branch === 'object' ? item.branch.name : item.branch;

        return (
            <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
                <DialogContent className="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>View Customer</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        {/* Basic Info Section */}
                        <ViewField label="Name" value={item.name} />
                        <ViewField label="Email" value={item.email} />
                        <ViewField label="Phone" value={item.phone} />
                        <ViewField label="Address" value={item.address} />

                        {/* Details Section */}
                        <ViewField label="Branch" value={branchName} />
                        <ViewField
                            label="Category"
                            value={
                                typeof item.category === 'object'
                                    ? item.category.name
                                    : item.category
                            }
                        />
                        <div className="flex items-center justify-between">
                            <span className="text-sm font-medium text-muted-foreground">
                                Status
                            </span>
                            <Badge
                                variant={
                                    item.status === 'active'
                                        ? 'default'
                                        : 'destructive'
                                }
                            >
                                {item.status === 'active' ? 'Active' : 'Inactive'}
                            </Badge>
                        </div>

                        {/* Notes Section */}
                        {item.notes && (
                            <ViewField label="Notes" value={item.notes} />
                        )}
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
