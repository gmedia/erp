'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { useMemo } from 'react';
import { type Resolver, useForm } from 'react-hook-form';
import { z } from 'zod';

import { InputField } from '@/components/common/InputField';
import {
    ItemAccountSelectField,
    ItemFormDialogShell,
    ItemNotesField,
    ItemProductUnitFields,
} from '@/components/common/ItemFormDialog';
import { ItemPricingFields } from '@/components/common/ItemPricingFields';
import { useTranslation } from '@/contexts/i18n-context';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import { type CustomerInvoiceFormData } from '@/types/customer-invoice';

const customerInvoiceItemSchema = z.object({
    product_id: z.string().optional(),
    product_label: z.string().optional(),
    account_id: z.string().min(1, {
        message: 'validation.customer_invoice.account_required',
    }),
    account_label: z.string().optional(),
    unit_id: z.string().optional(),
    unit_label: z.string().optional(),
    description: z.string().min(1, {
        message: 'validation.customer_invoice.description_required',
    }),
    quantity: z.coerce.number().gt(0, {
        message: 'validation.customer_invoice.quantity_gt_zero',
    }),
    unit_price: z.coerce.number().min(0, {
        message: 'validation.customer_invoice.unit_price_min_zero',
    }),
    discount_percent: z.coerce.number().min(0).max(100).optional().default(0),
    tax_percent: z.coerce.number().min(0).max(100).optional().default(0),
    notes: z.string().optional(),
});

type CustomerInvoiceItemFormData = CustomerInvoiceFormData['items'][number];

interface CustomerInvoiceItemFormDialogProps {
    readonly open: boolean;
    readonly onOpenChange: (open: boolean) => void;
    readonly item: CustomerInvoiceItemFormData | null;
    readonly onSave: (data: CustomerInvoiceItemFormData) => void;
}

export function CustomerInvoiceItemFormDialog({
    open,
    onOpenChange,
    item,
    onSave,
}: CustomerInvoiceItemFormDialogProps) {
    const { t } = useTranslation();
    const fl = (key: string, fallback: string) => {
        const translated = t(`form_fields.customer_invoices.${key}`);
        return translated === `form_fields.customer_invoices.${key}`
            ? fallback
            : translated;
    };
    const defaultValues = useMemo<CustomerInvoiceItemFormData>(() => {
        if (!item) {
            return {
                product_id: '',
                product_label: '',
                account_id: '',
                account_label: '',
                unit_id: '',
                unit_label: '',
                description: '',
                quantity: 1,
                unit_price: 0,
                discount_percent: 0,
                tax_percent: 0,
                notes: '',
            };
        }

        return {
            product_id: item.product_id || '',
            product_label: item.product_label || '',
            account_id: item.account_id || '',
            account_label: item.account_label || '',
            unit_id: item.unit_id || '',
            unit_label: item.unit_label || '',
            description: item.description || '',
            quantity: Number(item.quantity || 0),
            unit_price: Number(item.unit_price || 0),
            discount_percent: Number(item.discount_percent || 0),
            tax_percent: Number(item.tax_percent || 0),
            notes: item.notes || '',
        };
    }, [item]);

    const form = useForm<
        CustomerInvoiceItemFormData,
        unknown,
        CustomerInvoiceItemFormData
    >({
        resolver: zodResolver(customerInvoiceItemSchema) as Resolver<
            CustomerInvoiceItemFormData,
            unknown,
            CustomerInvoiceItemFormData
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
            itemDescription="invoice item"
        >
            <ItemProductUnitFields
                form={form}
                open={open}
                productInitialId={defaultValues.product_id}
                productInitialLabel={defaultValues.product_label}
                unitInitialId={defaultValues.unit_id}
                unitInitialLabel={defaultValues.unit_label}
            />
            <div className="grid grid-cols-1 gap-4">
                <ItemAccountSelectField
                    form={form}
                    open={open}
                    name="account_id"
                    labelName="account_label"
                    initialId={defaultValues.account_id}
                    initialLabel={defaultValues.account_label}
                />
                <InputField
                    name="description"
                    label={fl('item_description', 'Description')}
                    placeholder={fl(
                        'item_description_placeholder',
                        'Enter description',
                    )}
                />
            </div>
            <ItemPricingFields />
            <ItemNotesField />
        </ItemFormDialogShell>
    );
}
