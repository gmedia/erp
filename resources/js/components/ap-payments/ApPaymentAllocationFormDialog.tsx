'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { useMemo } from 'react';
import { type Resolver, useForm } from 'react-hook-form';
import { z } from 'zod';

import { InputField } from '@/components/common/InputField';
import { ItemFormDialogShell } from '@/components/common/ItemFormDialog';
import { TextareaField } from '@/components/common/TextareaField';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import { type ApPaymentFormData } from '@/types/ap-payment';

const apPaymentAllocationSchema = z.object({
    supplier_bill_id: z.string().min(1, { message: 'Bill is required.' }),
    bill_label: z.string().optional(),
    allocated_amount: z.coerce
        .number()
        .gt(0, { message: 'Allocated amount must be greater than 0.' }),
    discount_taken: z.coerce.number().min(0).optional().default(0),
    notes: z.string().optional(),
});

type ApPaymentAllocationFormData = ApPaymentFormData['allocations'][number];

interface ApPaymentAllocationFormDialogProps {
    readonly open: boolean;
    readonly onOpenChange: (open: boolean) => void;
    readonly item: ApPaymentAllocationFormData | null;
    readonly onSave: (data: ApPaymentAllocationFormData) => void;
}

export function ApPaymentAllocationFormDialog({
    open,
    onOpenChange,
    item,
    onSave,
}: ApPaymentAllocationFormDialogProps) {
    const defaultValues = useMemo<ApPaymentAllocationFormData>(() => {
        if (!item) {
            return {
                supplier_bill_id: '',
                bill_label: '',
                allocated_amount: 0,
                discount_taken: 0,
                notes: '',
            };
        }

        return {
            supplier_bill_id: item.supplier_bill_id || '',
            bill_label: item.bill_label || '',
            allocated_amount: Number(item.allocated_amount || 0),
            discount_taken: Number(item.discount_taken || 0),
            notes: item.notes || '',
        };
    }, [item]);

    const form = useForm<
        ApPaymentAllocationFormData,
        unknown,
        ApPaymentAllocationFormData
    >({
        resolver: zodResolver(apPaymentAllocationSchema) as Resolver<
            ApPaymentAllocationFormData,
            unknown,
            ApPaymentAllocationFormData
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
            itemDescription="payment allocation"
        >
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <InputField
                    name="supplier_bill_id"
                    label="Supplier Bill ID"
                    placeholder="Supplier Bill ID"
                    disabled
                />
                <InputField
                    name="allocated_amount"
                    label="Allocated Amount"
                    type="number"
                    min={0}
                    step="any"
                    placeholder="0"
                />
                <InputField
                    name="discount_taken"
                    label="Discount Taken"
                    type="number"
                    min={0}
                    step="any"
                    placeholder="0"
                />
            </div>

            <TextareaField
                name="notes"
                label="Notes"
                placeholder="Allocation notes"
                rows={2}
            />
        </ItemFormDialogShell>
    );
}