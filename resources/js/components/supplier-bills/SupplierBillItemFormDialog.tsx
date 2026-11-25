'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { useMemo } from 'react';
import { type Resolver, useForm } from 'react-hook-form';
import { z } from 'zod';

import { InputField } from '@/components/common/InputField';
import {
    ItemAccountSelectField,
    ItemFormDialogShell,
} from '@/components/common/ItemFormDialog';
import { ItemPricingFields } from '@/components/common/ItemPricingFields';
import { TextareaField } from '@/components/common/TextareaField';
import { useTranslation } from '@/contexts/i18n-context';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import { type SupplierBillFormData } from '@/types/supplier-bill';
import { transactionItemPricingSchema } from '@/utils/schemas';

const supplierBillItemSchema = z.object({
    product_id: z.string().optional(),
    product_label: z.string().optional(),
    account_id: z.string().min(1, {
        message: 'validation.supplier_bill.account_required',
    }),
    account_label: z.string().optional(),
    description: z.string().min(1, {
        message: 'validation.supplier_bill.description_required',
    }),
    ...transactionItemPricingSchema,
    goods_receipt_item_id: z.string().optional(),
    notes: z.string().optional(),
});

type SupplierBillItemFormData = SupplierBillFormData['items'][number];

interface SupplierBillItemFormDialogProps {
    readonly open: boolean;
    readonly onOpenChange: (open: boolean) => void;
    readonly item: SupplierBillItemFormData | null;
    readonly onSave: (data: SupplierBillItemFormData) => void;
}

export function SupplierBillItemFormDialog({
    open,
    onOpenChange,
    item,
    onSave,
}: SupplierBillItemFormDialogProps) {
    const { t } = useTranslation();
    const fl = (key: string, fallback: string) => {
        const translated = t(`form_fields.supplier_bills.${key}`);
        return translated === `form_fields.supplier_bills.${key}`
            ? fallback
            : translated;
    };
    const defaultValues = useMemo<SupplierBillItemFormData>(() => {
        if (!item) {
            return {
                product_id: '',
                product_label: '',
                account_id: '',
                account_label: '',
                description: '',
                quantity: 1,
                unit_price: 0,
                discount_percent: 0,
                tax_percent: 0,
                goods_receipt_item_id: '',
                notes: '',
            };
        }

        return {
            product_id: item.product_id || '',
            product_label: item.product_label || '',
            account_id: item.account_id || '',
            account_label: item.account_label || '',
            description: item.description || '',
            quantity: Number(item.quantity || 0),
            unit_price: Number(item.unit_price || 0),
            discount_percent: Number(item.discount_percent || 0),
            tax_percent: Number(item.tax_percent || 0),
            goods_receipt_item_id: item.goods_receipt_item_id || '',
            notes: item.notes || '',
        };
    }, [item]);

    const form = useForm<
        SupplierBillItemFormData,
        unknown,
        SupplierBillItemFormData
    >({
        resolver: zodResolver(supplierBillItemSchema) as Resolver<
            SupplierBillItemFormData,
            unknown,
            SupplierBillItemFormData
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
            itemDescription="bill item"
        >
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <InputField
                    name="description"
                    label={fl('item_description', 'Description')}
                    placeholder={fl(
                        'item_description_placeholder',
                        'Item description',
                    )}
                />
                <ItemAccountSelectField
                    form={form}
                    open={open}
                    name="account_id"
                    labelName="account_label"
                    initialId={defaultValues.account_id}
                    initialLabel={defaultValues.account_label}
                />
                <ItemPricingFields />
            </div>

            <TextareaField
                name="notes"
                label={fl('item_notes', 'Notes')}
                placeholder={fl('item_notes_placeholder', 'Item notes')}
                rows={2}
            />
        </ItemFormDialogShell>
    );
}
