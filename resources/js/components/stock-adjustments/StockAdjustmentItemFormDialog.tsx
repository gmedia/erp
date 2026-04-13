'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { useMemo } from 'react';
import { type Resolver, useForm } from 'react-hook-form';

import { InputField } from '@/components/common/InputField';
import {
    ItemFormDialogShell,
    ItemProductUnitFields,
} from '@/components/common/ItemFormDialog';
import { TextareaField } from '@/components/common/TextareaField';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import {
    stockAdjustmentItemSchema,
    type StockAdjustmentFormData,
} from '@/utils/schemas';

type StockAdjustmentItemFormData = StockAdjustmentFormData['items'][number];

interface StockAdjustmentItemFormDialogProps {
    readonly open: boolean;
    readonly onOpenChange: (open: boolean) => void;
    readonly item: StockAdjustmentItemFormData | null;
    readonly onSave: (data: StockAdjustmentItemFormData) => void;
}

export function StockAdjustmentItemFormDialog({
    open,
    onOpenChange,
    item,
    onSave,
}: Readonly<StockAdjustmentItemFormDialogProps>) {
    const defaultValues = useMemo<StockAdjustmentItemFormData>(() => {
        if (!item) {
            return {
                product_id: '',
                product_label: '',
                unit_id: '',
                unit_label: '',
                quantity_before: 0,
                quantity_adjusted: 1,
                unit_cost: 0,
                reason: '',
            };
        }

        return {
            product_id: item.product_id || '',
            product_label: item.product_label || '',
            unit_id: item.unit_id || '',
            unit_label: item.unit_label || '',
            quantity_before: Number(item.quantity_before || 0),
            quantity_adjusted: Number(item.quantity_adjusted || 0),
            unit_cost: Number(item.unit_cost || 0),
            reason: item.reason || '',
        };
    }, [item]);

    const form = useForm<
        StockAdjustmentItemFormData,
        unknown,
        StockAdjustmentItemFormData
    >({
        resolver: zodResolver(stockAdjustmentItemSchema) as Resolver<
            StockAdjustmentItemFormData,
            unknown,
            StockAdjustmentItemFormData
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
            itemDescription="stock adjustment item"
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
                    name="quantity_before"
                    label="Quantity Before"
                    type="number"
                    min={0}
                    step="any"
                    placeholder="0"
                />
                <InputField
                    name="quantity_adjusted"
                    label="Quantity Adjusted"
                    type="number"
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
                name="reason"
                label="Reason"
                placeholder="Item reason"
                rows={3}
            />
        </ItemFormDialogShell>
    );
}
