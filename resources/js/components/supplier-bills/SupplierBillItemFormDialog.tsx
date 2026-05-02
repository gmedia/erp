'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { useMemo } from 'react';
import { type Resolver, useForm } from 'react-hook-form';
import { z } from 'zod';

import { InputField } from '@/components/common/InputField';
import {
    ItemFormDialogShell,
    ItemNotesField,
} from '@/components/common/ItemFormDialog';
import { TextareaField } from '@/components/common/TextareaField';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import { type SupplierBillFormData } from '@/types/supplier-bill';

const supplierBillItemSchema = z.object({
    product_id: z.string().optional(),
    product_label: z.string().optional(),
    account_id: z.string().min(1, { message: 'Account is required.' }),
    account_label: z.string().optional(),
    description: z.string().min(1, { message: 'Description is required.' }),
    quantity: z.coerce
        .number()
        .gt(0, { message: 'Quantity must be greater than 0.' }),
    unit_price: z.coerce
        .number()
        .min(0, { message: 'Unit price must be at least 0.' }),
    discount_percent: z.coerce.number().min(0).max(100).optional().default(0),
    tax_percent: z.coerce.number().min(0).max(100).optional().default(0),
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
                    label="Description"
                    placeholder="Item description"
                />
                <InputField
                    name="account_id"
                    label="Account ID"
                    placeholder="Account ID"
                    disabled
                />
                <InputField
                    name="quantity"
                    label="Quantity"
                    type="number"
                    min={0}
                    step="any"
                    placeholder="1"
                />
                <InputField
                    name="unit_price"
                    label="Unit Price"
                    type="number"
                    min={0}
                    step="any"
                    placeholder="0"
                />
                <InputField
                    name="discount_percent"
                    label="Discount Percent"
                    type="number"
                    min={0}
                    max={100}
                    step="any"
                    placeholder="0"
                />
                <InputField
                    name="tax_percent"
                    label="Tax Percent"
                    type="number"
                    min={0}
                    max={100}
                    step="any"
                    placeholder="0"
                />
            </div>

            <TextareaField
                name="notes"
                label="Notes"
                placeholder="Item notes"
                rows={2}
            />
        </ItemFormDialogShell>
    );
}