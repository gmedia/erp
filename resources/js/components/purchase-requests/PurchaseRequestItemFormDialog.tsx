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
import { type PurchaseRequestFormData } from '@/types/purchase-request';

const purchaseRequestItemSchema = z.object({
    product_id: z.string().min(1, { message: 'Product is required.' }),
    product_label: z.string().optional(),
    unit_id: z.string().min(1, { message: 'Unit is required.' }),
    unit_label: z.string().optional(),
    quantity: z.coerce
        .number()
        .gt(0, { message: 'Quantity must be greater than 0.' }),
    estimated_unit_price: z.coerce.number().min(0).optional().default(0),
    notes: z.string().optional(),
});

type PurchaseRequestItemFormData = PurchaseRequestFormData['items'][number];

interface PurchaseRequestItemFormDialogProps {
    readonly open: boolean;
    readonly onOpenChange: (open: boolean) => void;
    readonly item: PurchaseRequestItemFormData | null;
    readonly onSave: (data: PurchaseRequestItemFormData) => void;
}

export function PurchaseRequestItemFormDialog({
    open,
    onOpenChange,
    item,
    onSave,
}: PurchaseRequestItemFormDialogProps) {
    const defaultValues = useMemo<PurchaseRequestItemFormData>(() => {
        if (!item) {
            return {
                product_id: '',
                product_label: '',
                unit_id: '',
                unit_label: '',
                quantity: 1,
                estimated_unit_price: 0,
                notes: '',
            };
        }

        return {
            product_id: item.product_id || '',
            product_label: item.product_label || '',
            unit_id: item.unit_id || '',
            unit_label: item.unit_label || '',
            quantity: Number(item.quantity || 0),
            estimated_unit_price: Number(item.estimated_unit_price || 0),
            notes: item.notes || '',
        };
    }, [item]);

    const form = useForm<
        PurchaseRequestItemFormData,
        unknown,
        PurchaseRequestItemFormData
    >({
        resolver: zodResolver(purchaseRequestItemSchema) as Resolver<
            PurchaseRequestItemFormData,
            unknown,
            PurchaseRequestItemFormData
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
            itemDescription="purchase request item"
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
                    name="estimated_unit_price"
                    label="Est. Unit Price"
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
