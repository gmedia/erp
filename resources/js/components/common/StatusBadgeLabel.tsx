'use client';

import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/contexts/i18n-context';
import {
    formatStatusLabel,
    statusBadgeVariant,
    statusTranslationKey,
} from '@/lib/status-badge';

interface StatusBadgeLabelProps {
    status: string | null | undefined;
}

export function StatusBadgeLabel({ status }: Readonly<StatusBadgeLabelProps>) {
    const { t } = useTranslation();
    const translationKey = statusTranslationKey(status);

    return (
        <Badge variant={statusBadgeVariant(status)}>
            {translationKey ? t(translationKey) : formatStatusLabel(status)}
        </Badge>
    );
}
