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

interface DeleteConfirmationDialogProps<T> {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    item: T | null;
    onConfirm: () => void;
    isLoading?: boolean;
    getDeleteMessage: (item: T) => string;
    title?: string;
}

export function DeleteConfirmationDialog<T>({
    open,
    onOpenChange,
    item,
    onConfirm,
    isLoading = false,
    getDeleteMessage,
    title,
}: DeleteConfirmationDialogProps<T>) {
    const { t } = useTranslation();

    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {title || t('dialog.are_you_sure')}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {item && getDeleteMessage(item)}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={isLoading}>
                        {t('common.cancel')}
                    </AlertDialogCancel>
                    <AlertDialogAction
                        onClick={onConfirm}
                        disabled={isLoading}
                        className="bg-destructive hover:bg-destructive/80 text-white"
                    >
                        {isLoading ? t('common.deleting') : t('common.delete')}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
