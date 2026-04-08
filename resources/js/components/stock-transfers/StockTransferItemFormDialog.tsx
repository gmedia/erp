'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { useMemo } from 'react';
import { type Resolver, useForm } from 'react-hook-form';
import { z } from 'zod';

import { InputField } from '@/components/common/InputField';
import {
    ItemFormDialogShell,
    ItemProductSelectField,
    ItemUnitSelectField,
} from '@/components/common/ItemFormDialog';
import { TextareaField } from '@/components/common/TextareaField';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import { type StockTransferFormData } from '@/utils/schemas';

const stockTransferItemSchema = z.object({
    product_id: z.string().min(1, { message: 'Product is required.' }),
    product_label: z.string().optional(),
    unit_id: z.string().min(1, { message: 'Unit is required.' }),
    unit_label: z.string().optional(),
    quantity: z.coerce
        .number()
        .gt(0, { message: 'Quantity must be greater than 0.' }),
    quantity_received: z.coerce.number().min(0).optional().default(0),
    unit_cost: z.coerce.number().min(0).optional().default(0),
    notes: z.string().optional(),
});

type StockTransferItemFormData = StockTransferFormData['items'][number];

interface StockTransferItemFormDialogProps {
    readonly open: boolean;
    readonly onOpenChange: (open: boolean) => void;
    readonly item: StockTransferItemFormData | null;
    readonly onSave: (data: StockTransferItemFormData) => void;
}

export function StockTransferItemFormDialog({
    open,
    onOpenChange,
    item,
    onSave,
}: Readonly<StockTransferItemFormDialogProps>) {
    const defaultValues = useMemo<StockTransferItemFormData>(() => {
        if (!item) {
            return {
                product_id: '',
                product_label: '',
                unit_id: '',
                unit_label: '',
                quantity: 1,
                quantity_received: 0,
                unit_cost: 0,
                notes: '',
            };
        }

        return {
            product_id: item.product_id || '',
            product_label: item.product_label || '',
            unit_id: item.unit_id || '',
            unit_label: item.unit_label || '',
            quantity: Number(item.quantity || 0),
            quantity_received: Number(item.quantity_received || 0),
            unit_cost: Number(item.unit_cost || 0),
            notes: item.notes || '',
        };
    }, [item]);

    const form = useForm<
        StockTransferItemFormData,
        unknown,
        StockTransferItemFormData
    >({
        resolver: zodResolver(stockTransferItemSchema) as Resolver<
            StockTransferItemFormData,
            unknown,
            StockTransferItemFormData
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
            itemDescription="stock transfer item"
        >
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <ItemProductSelectField
                    form={form}
                    open={open}
                    name="product_id"
                    labelName="product_label"
                    label="Product"
                    url="/api/products"
                    placeholder="Select product"
                    initialId={defaultValues.product_id}
                    initialLabel={defaultValues.product_label}
                />
                <ItemUnitSelectField
                    form={form}
                    open={open}
                    name="unit_id"
                    labelName="unit_label"
                    label="Unit"
                    url="/api/units"
                    placeholder="Select unit"
                    initialId={defaultValues.unit_id}
                    initialLabel={defaultValues.unit_label}
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
                    name="quantity_received"
                    label="Quantity Received"
                    type="number"
                    min={0}
                    step="any"
                    placeholder="0"
                />
                <InputField
                    name="unit_cost"
                    label="Unit Cost"
                    type="number"
                    min={0}
                    step="any"
                    placeholder="0"
                />
            </div>

            <TextareaField
                name="notes"
                label="Notes"
                placeholder="Item notes"
                rows={3}
            />
        </ItemFormDialogShell>
    );
}
