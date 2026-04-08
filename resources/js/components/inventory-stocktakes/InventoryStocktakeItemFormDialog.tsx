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
import { type InventoryStocktakeFormData } from '@/utils/schemas';

const inventoryStocktakeItemSchema = z.object({
    product_id: z.string().min(1, { message: 'Product is required.' }),
    product_label: z.string().optional(),
    unit_id: z.string().min(1, { message: 'Unit is required.' }),
    unit_label: z.string().optional(),
    system_quantity: z.coerce
        .number()
        .min(0, { message: 'System quantity must be at least 0.' }),
    counted_quantity: z.coerce
        .number()
        .min(0, { message: 'Counted quantity must be at least 0.' })
        .optional()
        .default(0),
    notes: z.string().optional(),
});

type InventoryStocktakeItemFormData =
    InventoryStocktakeFormData['items'][number];

interface InventoryStocktakeItemFormDialogProps {
    readonly open: boolean;
    readonly onOpenChange: (open: boolean) => void;
    readonly item: InventoryStocktakeItemFormData | null;
    readonly onSave: (data: InventoryStocktakeItemFormData) => void;
}

export function InventoryStocktakeItemFormDialog({
    open,
    onOpenChange,
    item,
    onSave,
}: Readonly<InventoryStocktakeItemFormDialogProps>) {
    const defaultValues = useMemo<InventoryStocktakeItemFormData>(() => {
        if (!item) {
            return {
                product_id: '',
                product_label: '',
                unit_id: '',
                unit_label: '',
                system_quantity: 0,
                counted_quantity: 0,
                notes: '',
            };
        }

        return {
            product_id: item.product_id || '',
            product_label: item.product_label || '',
            unit_id: item.unit_id || '',
            unit_label: item.unit_label || '',
            system_quantity: Number(item.system_quantity || 0),
            counted_quantity:
                item.counted_quantity === null ||
                item.counted_quantity === undefined
                    ? 0
                    : Number(item.counted_quantity),
            notes: item.notes || '',
        };
    }, [item]);

    const form = useForm<
        InventoryStocktakeItemFormData,
        unknown,
        InventoryStocktakeItemFormData
    >({
        resolver: zodResolver(inventoryStocktakeItemSchema) as Resolver<
            InventoryStocktakeItemFormData,
            unknown,
            InventoryStocktakeItemFormData
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
            itemDescription="inventory stocktake item"
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
                    name="system_quantity"
                    label="System Quantity"
                    type="number"
                    min={0}
                    step="any"
                    placeholder="0"
                />
                <InputField
                    name="counted_quantity"
                    label="Counted Quantity"
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
