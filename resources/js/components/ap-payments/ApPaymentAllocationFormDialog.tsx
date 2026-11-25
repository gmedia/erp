'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { useMemo } from 'react';
import { type Resolver, useForm } from 'react-hook-form';
import { z } from 'zod';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { InputField } from '@/components/common/InputField';
import { ItemFormDialogShell } from '@/components/common/ItemFormDialog';
import { TextareaField } from '@/components/common/TextareaField';
import { useTranslation } from '@/contexts/i18n-context';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import { type ApPaymentFormData } from '@/types/ap-payment';
import { type SupplierBill } from '@/types/supplier-bill';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';

type ApPaymentAllocationFormData = ApPaymentFormData['allocations'][number];

interface ApPaymentAllocationFormDialogProps {
    readonly open: boolean;
    readonly onOpenChange: (open: boolean) => void;
    readonly item: ApPaymentAllocationFormData | null;
    readonly onSave: (data: ApPaymentAllocationFormData) => void;
    readonly supplierId?: string;
}

const OPEN_BILL_STATUSES: readonly string[] = [
    'confirmed',
    'partially_paid',
    'overdue',
];

const isOpenBill = (bill: SupplierBill): boolean =>
    OPEN_BILL_STATUSES.includes(bill.status) && Number(bill.amount_due) > 0;

const billLabel = (bill: SupplierBill): string =>
    `${bill.bill_number ?? String(bill.id)} — ${formatCurrencyByRegionalSettings(
        bill.amount_due,
        {
            locale: 'id-ID',
            currency: bill.currency || 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        },
    )}`;

export function ApPaymentAllocationFormDialog({
    open,
    onOpenChange,
    item,
    onSave,
    supplierId,
}: ApPaymentAllocationFormDialogProps) {
    const { t } = useTranslation();
    const apPaymentAllocationSchema = useMemo(
        () =>
            z.object({
                supplier_bill_id: z.string().min(1, {
                    message: 'validation.ap_payment.bill_required',
                }),
                bill_label: z.string().optional(),
                allocated_amount: z.coerce.number().gt(0, {
                    message: 'validation.ap_payment.amount_gt_zero',
                }),
                discount_taken: z.coerce.number().min(0).optional().default(0),
                notes: z.string().optional(),
            }),
        [],
    );
    const defaultValues = useMemo<ApPaymentAllocationFormData>(() => {
        if (!item) {
            return {
                supplier_bill_id: '',
                bill_label: '',
                allocated_amount: 0,
                discount_taken: 0,
                notes: '',
            };
        }

        return {
            supplier_bill_id: item.supplier_bill_id || '',
            bill_label: item.bill_label || '',
            allocated_amount: Number(item.allocated_amount || 0),
            discount_taken: Number(item.discount_taken || 0),
            notes: item.notes || '',
        };
    }, [item]);

    const form = useForm<
        ApPaymentAllocationFormData,
        unknown,
        ApPaymentAllocationFormData
    >({
        resolver: zodResolver(apPaymentAllocationSchema) as Resolver<
            ApPaymentAllocationFormData,
            unknown,
            ApPaymentAllocationFormData
        >,
        defaultValues,
    });

    useResetFormOnDefaultValues(form, defaultValues, { enabled: open });

    return (
        <ItemFormDialogShell
            open={open}
            onOpenChange={onOpenChange}
            item={item}
            form={form}
            onSave={onSave}
            itemDescription="payment allocation"
            titleLabel={t('apPayment.allocation.title_label')}
            actionLabel={t('apPayment.allocation.title_label')}
        >
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                {supplierId ? (
                    <AsyncSelectField<SupplierBill>
                        name="supplier_bill_id"
                        label={t('apPayment.allocation.bill')}
                        placeholder={t('apPayment.allocation.bill_placeholder')}
                        url={`/api/supplier-bills?supplier_id=${supplierId}`}
                        labelFn={billLabel}
                        valueFn={(bill) => String(bill.id)}
                        filterItems={isOpenBill}
                        initialLabel={defaultValues.bill_label || undefined}
                        onItemSelect={(bill) => {
                            form.setValue('bill_label', bill.bill_number ?? '');
                            form.setValue(
                                'allocated_amount',
                                Number(bill.amount_due),
                            );
                        }}
                    />
                ) : (
                    <p className="text-sm text-muted-foreground md:col-span-2">
                        {t('apPayment.allocation.select_supplier_first')}
                    </p>
                )}
                <InputField
                    name="allocated_amount"
                    label={t('apPayment.allocation.allocated_amount')}
                    type="number"
                    min={0}
                    step="any"
                    placeholder="0"
                />
                <InputField
                    name="discount_taken"
                    label={t('apPayment.allocation.discount_taken')}
                    type="number"
                    min={0}
                    step="any"
                    placeholder="0"
                />
            </div>

            <TextareaField
                name="notes"
                label={t('apPayment.allocation.notes')}
                placeholder={t('apPayment.allocation.notes_placeholder')}
                rows={2}
            />
        </ItemFormDialogShell>
    );
}
