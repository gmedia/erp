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
import { transactionItemPricingSchema } from '@/utils/schemas';
import { type PurchaseOrderFormData } from '@/types/purchase-order';

const purchaseOrderItemSchema = z.object({
    purchase_request_item_id: z.string().optional(),
    product_id: z.string().min(1, { message: 'Product is required.' }),
    product_label: z.string().optional(),
    unit_id: z.string().min(1, { message: 'Unit is required.' }),
    unit_label: z.string().optional(),
    ...transactionItemPricingSchema,
    notes: z.string().optional(),
});

type PurchaseOrderItemFormData = PurchaseOrderFormData['items'][number];

interface PurchaseOrderItemFormDialogProps {
    readonly open: boolean;
    readonly onOpenChange: (open: boolean) => void;
    readonly item: PurchaseOrderItemFormData | null;
    readonly onSave: (data: PurchaseOrderItemFormData) => void;
}

export function PurchaseOrderItemFormDialog({
    open,
    onOpenChange,
    item,
    onSave,
}: PurchaseOrderItemFormDialogProps) {
    const defaultValues = useMemo<PurchaseOrderItemFormData>(() => {
        if (!item) {
            return {
                purchase_request_item_id: '',
                product_id: '',
                product_label: '',
                unit_id: '',
                unit_label: '',
                quantity: 1,
                unit_price: 0,
                discount_percent: 0,
                tax_percent: 0,
                notes: '',
            };
        }

        return {
            purchase_request_item_id: item.purchase_request_item_id || '',
            product_id: item.product_id || '',
            product_label: item.product_label || '',
            unit_id: item.unit_id || '',
            unit_label: item.unit_label || '',
            quantity: Number(item.quantity || 0),
            unit_price: Number(item.unit_price || 0),
            discount_percent: Number(item.discount_percent || 0),
            tax_percent: Number(item.tax_percent || 0),
            notes: item.notes || '',
        };
    }, [item]);

    const form = useForm<
        PurchaseOrderItemFormData,
        unknown,
        PurchaseOrderItemFormData
    >({
        resolver: zodResolver(purchaseOrderItemSchema) as Resolver<
            PurchaseOrderItemFormData,
            unknown,
            PurchaseOrderItemFormData
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
            itemDescription="purchase order item"
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

            <ItemNotesField />
        </ItemFormDialogShell>
    );
}
