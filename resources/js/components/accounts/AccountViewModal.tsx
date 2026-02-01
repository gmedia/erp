'use client';

import * as React from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { type Account } from '@/types/account';
import { Badge } from '@/components/ui/badge';

interface AccountViewModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    account: Account | null;
}

export function AccountViewModal({
    open,
    onOpenChange,
    account,
}: AccountViewModalProps) {
    if (!account) return null;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>{account.code} - {account.name}</DialogTitle>
                    <DialogDescription>
                        Account details for {account.code}
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-4 py-4">
                    <div className="grid grid-cols-4 items-center gap-4">
                        <span className="text-sm font-semibold">Type:</span>
                        <span className="col-span-3 capitalize">{account.type}</span>
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <span className="text-sm font-semibold">Balance:</span>
                        <Badge variant="secondary" className="col-span-3 capitalize w-fit">
                            {account.normal_balance}
                        </Badge>
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <span className="text-sm font-semibold">Status:</span>
                        <Badge variant={account.is_active ? 'default' : 'destructive'} className="col-span-3 w-fit">
                            {account.is_active ? 'Active' : 'Inactive'}
                        </Badge>
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <span className="text-sm font-semibold">Cash Flow:</span>
                        <span className="col-span-3">{account.is_cash_flow ? 'Yes' : 'No'}</span>
                    </div>
                    {account.description && (
                        <div className="grid grid-cols-4 items-start gap-4">
                            <span className="text-sm font-semibold">Description:</span>
                            <p className="col-span-3 text-sm text-muted-foreground">
                                {account.description}
                            </p>
                        </div>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
