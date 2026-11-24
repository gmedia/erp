'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { useMemo } from 'react';
import { type Resolver, useForm } from 'react-hook-form';
import { z } from 'zod';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { InputField } from '@/components/common/InputField';
import {
    ItemFormDialogShell,
    ItemNotesField,
} from '@/components/common/ItemFormDialog';
import { useTranslation } from '@/contexts/i18n-context';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import { type ArReceiptFormData } from '@/types/ar-receipt';
import { type CustomerInvoice } from '@/types/customer-invoice';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';

const OPEN_INVOICE_STATUSES: readonly string[] = [
    'sent',
    'partially_paid',
    'overdue',
];

const isOpenInvoice = (invoice: CustomerInvoice): boolean =>
    OPEN_INVOICE_STATUSES.includes(invoice.status) &&
    Number(invoice.amount_due) > 0;

type ArReceiptAllocationFormData = ArReceiptFormData['allocations'][number];

interface ArReceiptAllocationFormDialogProps {
    readonly open: boolean;
    readonly onOpenChange: (open: boolean) => void;
    readonly item: ArReceiptAllocationFormData | null;
    readonly onSave: (data: ArReceiptAllocationFormData) => void;
    readonly customerId?: string;
}

const invoiceLabel = (invoice: CustomerInvoice): string =>
    `${invoice.invoice_number ?? String(invoice.id)} — ${formatCurrencyByRegionalSettings(
        invoice.amount_due,
        {
            locale: 'id-ID',
            currency: invoice.currency || 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        },
    )}`;

export function ArReceiptAllocationFormDialog({
    open,
    onOpenChange,
    item,
    onSave,
    customerId,
}: ArReceiptAllocationFormDialogProps) {
    const { t } = useTranslation();
    const arReceiptAllocationSchema = useMemo(
        () =>
            z.object({
                customer_invoice_id: z.string().min(1, {
                    message: t('arReceipt.allocation.invoice_required'),
                }),
                invoice_label: z.string().optional(),
                allocated_amount: z.coerce.number().gt(0, {
                    message: t('arReceipt.allocation.amount_positive'),
                }),
                discount_given: z.coerce.number().min(0).optional().default(0),
                notes: z.string().optional(),
            }),
        [t],
    );
    const defaultValues = useMemo<ArReceiptAllocationFormData>(() => {
        if (!item) {
            return {
                customer_invoice_id: '',
                invoice_label: '',
                allocated_amount: 0,
                discount_given: 0,
                notes: '',
            };
        }

        return {
            customer_invoice_id: item.customer_invoice_id || '',
            invoice_label: item.invoice_label || '',
            allocated_amount: Number(item.allocated_amount || 0),
            discount_given: Number(item.discount_given || 0),
            notes: item.notes || '',
        };
    }, [item]);

    const form = useForm<
        ArReceiptAllocationFormData,
        unknown,
        ArReceiptAllocationFormData
    >({
        resolver: zodResolver(arReceiptAllocationSchema) as Resolver<
            ArReceiptAllocationFormData,
            unknown,
            ArReceiptAllocationFormData
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
            itemDescription="allocation"
            titleLabel={t('arReceipt.allocation.title_label')}
            actionLabel={t('arReceipt.allocation.title_label')}
        >
            <div className="grid grid-cols-1 gap-4">
                {customerId ? (
                    <AsyncSelectField<CustomerInvoice>
                        name="customer_invoice_id"
                        label={t('arReceipt.allocation.invoice')}
                        placeholder={t(
                            'arReceipt.allocation.invoice_placeholder',
                        )}
                        url={`/api/customer-invoices?customer_id=${customerId}`}
                        labelFn={invoiceLabel}
                        valueFn={(invoice) => String(invoice.id)}
                        filterItems={isOpenInvoice}
                        initialLabel={defaultValues.invoice_label || undefined}
                        onItemSelect={(invoice) => {
                            form.setValue(
                                'invoice_label',
                                invoice.invoice_number ?? '',
                            );
                            form.setValue(
                                'allocated_amount',
                                Number(invoice.amount_due),
                            );
                        }}
                    />
                ) : (
                    <p className="text-sm text-muted-foreground">
                        {t('arReceipt.allocation.select_customer_first')}
                    </p>
                )}
                <InputField
                    name="allocated_amount"
                    label={t('arReceipt.allocation.allocated_amount')}
                    type="number"
                    min={0}
                    step={0.01}
                />
                <InputField
                    name="discount_given"
                    label={t('arReceipt.allocation.discount_given')}
                    type="number"
                    min={0}
                    step={0.01}
                />
            </div>
            <ItemNotesField
                label={t('arReceipt.allocation.notes')}
                placeholder={t('arReceipt.allocation.notes_placeholder')}
            />
        </ItemFormDialogShell>
    );
}
