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
import { type AccountMapping } from '@/types/account-mapping';
import * as React from 'react';
import { memo } from 'react';

interface AccountMappingViewModalProps {
    open: boolean;
    onClose: () => void;
    item: AccountMapping | null;
}

function formatAccount(account?: {
    coa_version?: { name: string } | null;
    code: string;
    name: string;
} | null): string {
    if (!account) return '-';
    const version = account.coa_version?.name;
    const base = `${account.code} - ${account.name}`;
    return version ? `${version} • ${base}` : base;
}

export const AccountMappingViewModal = memo<AccountMappingViewModalProps>(
    function AccountMappingViewModal({ open, onClose, item }) {
        const { t } = useTranslation();
        if (!item) return null;

        return (
            <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
                <DialogContent className="sm:max-w-[520px]">
                    <DialogHeader>
                        <DialogTitle>View Account Mapping</DialogTitle>
                        <DialogDescription>{t('common.view_details')}</DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <ViewField label="Source Account" value={formatAccount(item.source_account)} />
                        <ViewField label="Target Account" value={formatAccount(item.target_account)} />
                        <ViewField label="Type" value={item.type.toUpperCase()} />
                        <ViewField label="Notes" value={item.notes || '-'} />
                        <ViewField label="Created At" value={formatDate(item.created_at)} />
                        <ViewField label="Updated At" value={formatDate(item.updated_at)} />
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
