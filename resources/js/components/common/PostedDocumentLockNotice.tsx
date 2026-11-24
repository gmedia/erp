'use client';

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { useTranslation } from '@/contexts/i18n-context';
import { LockKeyhole } from 'lucide-react';

interface PostedDocumentLockNoticeProps {
    readonly locked: boolean;
}

/**
 * Penjelasan di atas form dokumen terposting: nilai dan item terkunci,
 * perubahan lanjut lewat batal/void. Tidak tampil bila dokumen belum terkunci.
 */
export function PostedDocumentLockNotice({
    locked,
}: Readonly<PostedDocumentLockNoticeProps>) {
    const { t } = useTranslation();

    if (!locked) {
        return null;
    }

    return (
        <Alert>
            <LockKeyhole className="h-4 w-4" />
            <AlertTitle>{t('posted_document.title')}</AlertTitle>
            <AlertDescription>
                {t('posted_document.values_locked')}
            </AlertDescription>
        </Alert>
    );
}
