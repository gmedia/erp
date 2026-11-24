'use client';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { useTranslation } from '@/contexts/i18n-context';

export interface StatusActionConfig<A extends string> {
    action: A;
    label: string;
    title: string;
    description: string;
    confirmLabel: string;
}

interface StatusActionDialogProps<A extends string> {
    config: StatusActionConfig<A> | undefined;
    processing: boolean;
    processingLabel: string;
    onConfirm: () => void;
    onCancel: () => void;
}

export function StatusActionDialog<A extends string>({
    config,
    processing,
    processingLabel,
    onConfirm,
    onCancel,
}: Readonly<StatusActionDialogProps<A>>) {
    const { t } = useTranslation();

    return (
        <AlertDialog
            open={config !== undefined}
            onOpenChange={(next) => {
                if (!next && !processing) onCancel();
            }}
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{config?.title}</AlertDialogTitle>
                    <AlertDialogDescription>
                        {config?.description}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={processing}>
                        {t('common.cancel')}
                    </AlertDialogCancel>
                    <AlertDialogAction
                        onClick={(event) => {
                            event.preventDefault();
                            onConfirm();
                        }}
                        disabled={processing}
                    >
                        {processing ? processingLabel : config?.confirmLabel}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
