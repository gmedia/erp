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
import {
    formatSourceLineLabel,
    ItemSourceLineSelectField,
} from '@/components/common/ItemSourceLineSelectField';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import { type SupplierReturnFormData } from '@/types/supplier-return';

const supplierReturnItemSchema = z.object({
    goods_receipt_item_id: z
        .string()
        .min(1, { message: 'GR item is required.' }),
    goods_receipt_item_label: z.string().optional(),
    product_id: z.string().min(1, { message: 'Product is required.' }),
    product_label: z.string().optional(),
    unit_id: z.string().optional(),
    unit_label: z.string().optional(),
    quantity_returned: z.coerce
        .number()
        .gt(0, { message: 'Quantity returned must be greater than 0.' }),
    unit_price: z.coerce
        .number()
        .min(0, { message: 'Unit price must be at least 0.' }),
    notes: z.string().optional(),
});

type SupplierReturnItemFormData = SupplierReturnFormData['items'][number];

interface SupplierReturnItemFormDialogProps {
    readonly open: boolean;
    readonly onOpenChange: (open: boolean) => void;
    readonly item: SupplierReturnItemFormData | null;
    readonly onSave: (data: SupplierReturnItemFormData) => void;
    readonly goodsReceiptId?: string;
}

export function SupplierReturnItemFormDialog({
    open,
    onOpenChange,
    item,
    onSave,
    goodsReceiptId,
}: SupplierReturnItemFormDialogProps) {
    const defaultValues = useMemo<SupplierReturnItemFormData>(() => {
        if (!item) {
            return {
                goods_receipt_item_id: '',
                goods_receipt_item_label: '',
                product_id: '',
                product_label: '',
                unit_id: '',
                unit_label: '',
                quantity_returned: 1,
                unit_price: 0,
                notes: '',
            };
        }

        return {
            goods_receipt_item_id: item.goods_receipt_item_id || '',
            goods_receipt_item_label: item.goods_receipt_item_label || '',
            product_id: item.product_id || '',
            product_label: item.product_label || '',
            unit_id: item.unit_id || '',
            unit_label: item.unit_label || '',
            quantity_returned: Number(item.quantity_returned || 0),
            unit_price: Number(item.unit_price || 0),
            notes: item.notes || '',
        };
    }, [item]);

    const form = useForm<
        SupplierReturnItemFormData,
        unknown,
        SupplierReturnItemFormData
    >({
        resolver: zodResolver(supplierReturnItemSchema) as Resolver<
            SupplierReturnItemFormData,
            unknown,
            SupplierReturnItemFormData
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
            itemDescription="supplier return item"
        >
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <ItemSourceLineSelectField
                    open={open}
                    name="goods_receipt_item_id"
                    url={
                        goodsReceiptId
                            ? `/api/goods-receipts/${goodsReceiptId}/item-options${item?.goods_receipt_item_id ? `?current_line_id=${item.goods_receipt_item_id}` : ''}`
                            : null
                    }
                    label="GR Item"
                    placeholder="Select GR item"
                    emptyHint="Select a goods receipt first."
                    initialId={defaultValues.goods_receipt_item_id}
                    initialLabel={defaultValues.goods_receipt_item_label}
                    onLineSelect={(line) => {
                        form.setValue(
                            'goods_receipt_item_label',
                            formatSourceLineLabel(line),
                            { shouldDirty: true },
                        );
                        form.setValue(
                            'product_id',
                            String(line.product_id ?? ''),
                            {
                                shouldDirty: true,
                            },
                        );
                        form.setValue(
                            'product_label',
                            line.product_name ?? '',
                            {
                                shouldDirty: true,
                            },
                        );
                        form.setValue('unit_id', String(line.unit_id ?? ''), {
                            shouldDirty: true,
                        });
                        form.setValue('unit_label', line.unit_name ?? '', {
                            shouldDirty: true,
                        });
                        form.setValue(
                            'unit_price',
                            Number(line.unit_price ?? 0),
                            {
                                shouldDirty: true,
                            },
                        );
                    }}
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
                    name="quantity_returned"
                    label="Quantity Returned"
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
            </div>

            <ItemNotesField />
        </ItemFormDialogShell>
    );
}
