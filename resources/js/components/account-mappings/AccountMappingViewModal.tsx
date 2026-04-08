'use client';

import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { useTranslation } from '@/contexts/i18n-context';
import { formatDate } from '@/lib/utils';
import { type AccountMapping } from '@/types/account-mapping';
import { memo } from 'react';

interface AccountMappingViewModalProps {
    open: boolean;
    onClose: () => void;
    item: AccountMapping | null;
}

function formatAccount(
    account?: {
        coa_version?: { name: string } | null;
        code: string;
        name: string;
    } | null,
): string {
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
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="View Account Mapping"
                description={t('common.view_details')}
                contentClassName="sm:max-w-[520px]"
            >
                <div className="space-y-4 py-4">
                    <ViewField
                        label="Source Account"
                        value={formatAccount(item.source_account)}
                    />
                    <ViewField
                        label="Target Account"
                        value={formatAccount(item.target_account)}
                    />
                    <ViewField label="Type" value={item.type.toUpperCase()} />
                    <ViewField label="Notes" value={item.notes || '-'} />
                    <ViewField label="Created At" value={formatDate(item.created_at)} />
                    <ViewField label="Updated At" value={formatDate(item.updated_at)} />
                </div>
            </ViewModalShell>
        );
    },
);
