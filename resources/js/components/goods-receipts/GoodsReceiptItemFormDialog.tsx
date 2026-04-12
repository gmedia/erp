'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { useMemo } from 'react';
import { type Resolver, useForm } from 'react-hook-form';
import { z } from 'zod';

import { InputField } from '@/components/common/InputField';
import {
    ItemFormDialogShell,
    ItemNotesField,
    ItemProductUnitFields,
} from '@/components/common/ItemFormDialog';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import { type GoodsReceiptFormData } from '@/types/goods-receipt';

const goodsReceiptItemSchema = z.object({
    purchase_order_item_id: z
        .string()
        .min(1, { message: 'PO Item is required.' }),
    product_id: z.string().min(1, { message: 'Product is required.' }),
    product_label: z.string().optional(),
    unit_id: z.string().min(1, { message: 'Unit is required.' }),
    unit_label: z.string().optional(),
    quantity_received: z.coerce
        .number()
        .gt(0, { message: 'Quantity received must be greater than 0.' }),
    quantity_accepted: z.coerce
        .number()
        .min(0, { message: 'Quantity accepted must be at least 0.' }),
    quantity_rejected: z.coerce.number().min(0).optional().default(0),
    unit_price: z.coerce
        .number()
        .min(0, { message: 'Unit price must be at least 0.' }),
    notes: z.string().optional(),
});

type GoodsReceiptItemFormData = GoodsReceiptFormData['items'][number];

interface GoodsReceiptItemFormDialogProps {
    readonly open: boolean;
    readonly onOpenChange: (open: boolean) => void;
    readonly item: GoodsReceiptItemFormData | null;
    readonly onSave: (data: GoodsReceiptItemFormData) => void;
}

export function GoodsReceiptItemFormDialog({
    open,
    onOpenChange,
    item,
    onSave,
}: GoodsReceiptItemFormDialogProps) {
    const defaultValues = useMemo<GoodsReceiptItemFormData>(() => {
        if (!item) {
            return {
                purchase_order_item_id: '',
                product_id: '',
                product_label: '',
                unit_id: '',
                unit_label: '',
                quantity_received: 1,
                quantity_accepted: 1,
                quantity_rejected: 0,
                unit_price: 0,
                notes: '',
            };
        }

        return {
            purchase_order_item_id: item.purchase_order_item_id || '',
            product_id: item.product_id || '',
            product_label: item.product_label || '',
            unit_id: item.unit_id || '',
            unit_label: item.unit_label || '',
            quantity_received: Number(item.quantity_received || 0),
            quantity_accepted: Number(item.quantity_accepted || 0),
            quantity_rejected: Number(item.quantity_rejected || 0),
            unit_price: Number(item.unit_price || 0),
            notes: item.notes || '',
        };
    }, [item]);

    const form = useForm<
        GoodsReceiptItemFormData,
        unknown,
        GoodsReceiptItemFormData
    >({
        resolver: zodResolver(goodsReceiptItemSchema) as Resolver<
            GoodsReceiptItemFormData,
            unknown,
            GoodsReceiptItemFormData
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
            itemDescription="goods receipt item"
        >
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <InputField
                    name="purchase_order_item_id"
                    label="PO Item ID"
                    type="number"
                    min={1}
                    step="1"
                    placeholder="1"
                />
                <ItemProductUnitFields
                    form={form}
                    open={open}
                    productInitialId={defaultValues.product_id}
                    productInitialLabel={defaultValues.product_label}
                    unitInitialId={defaultValues.unit_id}
                    unitInitialLabel={defaultValues.unit_label}
                />
                <InputField
                    name="quantity_received"
                    label="Quantity Received"
                    type="number"
                    min={0}
                    step="any"
                    placeholder="1"
                />
                <InputField
                    name="quantity_accepted"
                    label="Quantity Accepted"
                    type="number"
                    min={0}
                    step="any"
                    placeholder="1"
                />
                <InputField
                    name="quantity_rejected"
                    label="Quantity Rejected"
                    type="number"
                    min={0}
                    step="any"
                    placeholder="0"
                />
                <InputField
                    name="unit_price"
                    label="Unit Price"
                    type="number"
                    min={0}
                    step="any"
                    placeholder="0"
                />
            </div>

            <ItemNotesField />
        </ItemFormDialogShell>
    );
}
