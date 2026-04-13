'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { useMemo } from 'react';
import { type Resolver, useForm } from 'react-hook-form';

import { InputField } from '@/components/common/InputField';
import {
    ItemFormDialogShell,
    ItemNotesField,
    ItemProductUnitFields,
} from '@/components/common/ItemFormDialog';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import {
    inventoryStocktakeItemSchema,
    type InventoryStocktakeFormData,
} from '@/utils/schemas';

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
                <ItemProductUnitFields
                    form={form}
                    open={open}
                    productInitialId={defaultValues.product_id}
                    productInitialLabel={defaultValues.product_label}
                    unitInitialId={defaultValues.unit_id}
                    unitInitialLabel={defaultValues.unit_label}
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

            <ItemNotesField />
        </ItemFormDialogShell>
    );
}
