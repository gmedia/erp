'use client';

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
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import { type ArReceiptFormData } from '@/types/ar-receipt';

const arReceiptAllocationSchema = z.object({
    customer_invoice_id: z.string().min(1, { message: 'Customer Invoice is required.' }),
    invoice_label: z.string().optional(),
    allocated_amount: z.coerce
        .number()
        .gt(0, { message: 'Allocated amount must be greater than 0.' }),
    discount_given: z.coerce.number().min(0).optional().default(0),
    notes: z.string().optional(),
});

type ArReceiptAllocationFormData = ArReceiptFormData['allocations'][number];

interface ArReceiptAllocationFormDialogProps {
    readonly open: boolean;
    readonly onOpenChange: (open: boolean) => void;
    readonly item: ArReceiptAllocationFormData | null;
    readonly onSave: (data: ArReceiptAllocationFormData) => void;
}

export function ArReceiptAllocationFormDialog({
    open,
    onOpenChange,
    item,
    onSave,
}: ArReceiptAllocationFormDialogProps) {
    const defaultValues = useMemo<ArReceiptAllocationFormData>(() => {
        if (!item) {
            return {
                customer_invoice_id: '',
                invoice_label: '',
                allocated_amount: 0,
                discount_given: 0,
                notes: '',
            };
        }

        return {
            customer_invoice_id: item.customer_invoice_id || '',
            invoice_label: item.invoice_label || '',
            allocated_amount: Number(item.allocated_amount || 0),
            discount_given: Number(item.discount_given || 0),
            notes: item.notes || '',
        };
    }, [item]);

    const form = useForm<
        ArReceiptAllocationFormData,
        unknown,
        ArReceiptAllocationFormData
    >({
        resolver: zodResolver(arReceiptAllocationSchema) as Resolver<
            ArReceiptAllocationFormData,
            unknown,
            ArReceiptAllocationFormData
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
            itemDescription="allocation"
        >
            <div className="grid grid-cols-1 gap-4">
                <InputField
                    name="customer_invoice_id"
                    label="Customer Invoice"
                    placeholder="Select Customer Invoice"
                />
                <InputField
                    name="allocated_amount"
                    label="Allocated Amount"
                    type="number"
                    min={0}
                    step={0.01}
                />
                <InputField
                    name="discount_given"
                    label="Discount Given"
                    type="number"
                    min={0}
                    step={0.01}
                />
            </div>
            <ItemNotesField />
        </ItemFormDialogShell>
    );
}