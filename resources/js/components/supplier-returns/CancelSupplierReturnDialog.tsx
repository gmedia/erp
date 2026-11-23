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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/contexts/i18n-context';
import axios from '@/lib/axios';
import { SupplierReturn } from '@/types/supplier-return';
import { handleApiError } from '@/utils/errorHandling';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useState, type MouseEvent } from 'react';
import { toast } from 'sonner';

interface CancelSupplierReturnDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    item: SupplierReturn | null;
    onCancelled?: () => void;
}

export function CancelSupplierReturnDialog({
    open,
    onOpenChange,
    item,
    onCancelled,
}: Readonly<CancelSupplierReturnDialogProps>) {
    const { t } = useTranslation();
    const queryClient = useQueryClient();
    const [reason, setReason] = useState('');
    const [reasonError, setReasonError] = useState<string | null>(null);

    const cancelMutation = useMutation({
        mutationFn: async (target: SupplierReturn) => {
            const response = await axios.put(
                `/api/supplier-returns/${target.id}`,
                { status: 'cancelled', notes: reason.trim() },
            );
            return response.data;
        },
        onSuccess: () => {
            void queryClient.invalidateQueries({
                queryKey: ['supplier-returns'],
            });
            toast.success(t('supplier_return.cancelled_success'));
            setReason('');
            setReasonError(null);
            onOpenChange(false);
            onCancelled?.();
        },
        onError: (error) => {
            const parsed = handleApiError(error, t('supplier_return.cancel'));
            setReasonError(parsed.message);
        },
    });

    const handleConfirm = (event: MouseEvent) => {
        event.preventDefault();
        if (!item) return;
        if (reason.trim() === '') {
            setReasonError(t('supplier_return.cancel_reason_required'));
            return;
        }
        setReasonError(null);
        cancelMutation.mutate(item);
    };

    const isBusy = cancelMutation.isPending;

    return (
        <AlertDialog
            open={open}
            onOpenChange={(next) => {
                if (isBusy) return;
                if (!next) {
                    setReason('');
                    setReasonError(null);
                }
                onOpenChange(next);
            }}
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {t('supplier_return.cancel_confirm_title')}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {t('supplier_return.cancel_confirm_description')}
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <div className="space-y-2">
                    <Label htmlFor="cancel-supplier-return-reason">
                        {t('supplier_return.cancel_reason')}
                    </Label>
                    <Textarea
                        id="cancel-supplier-return-reason"
                        data-testid="cancel-supplier-return-reason"
                        value={reason}
                        onChange={(e) => setReason(e.target.value)}
                        placeholder={t(
                            'supplier_return.cancel_reason_placeholder',
                        )}
                        disabled={isBusy}
                        aria-invalid={reasonError !== null}
                    />
                    {reasonError && (
                        <p
                            className="text-sm text-destructive"
                            data-testid="cancel-supplier-return-error"
                        >
                            {reasonError}
                        </p>
                    )}
                </div>

                <AlertDialogFooter>
                    <AlertDialogCancel disabled={isBusy}>
                        {t('common.cancel')}
                    </AlertDialogCancel>
                    <AlertDialogAction
                        onClick={handleConfirm}
                        disabled={isBusy}
                        data-testid="confirm-cancel-supplier-return"
                        className="bg-destructive text-white hover:bg-destructive/80"
                    >
                        {isBusy
                            ? t('supplier_return.cancelling')
                            : t('supplier_return.cancel')}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
