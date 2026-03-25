'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { useMemo } from 'react';
import { type Resolver, useForm } from 'react-hook-form';
import { z } from 'zod';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { InputField } from '@/components/common/InputField';
import { TextareaField } from '@/components/common/TextareaField';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Form } from '@/components/ui/form';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import { type StockAdjustmentFormData } from '@/utils/schemas';

const stockAdjustmentItemSchema = z.object({
    product_id: z.string().min(1, { message: 'Product is required.' }),
    product_label: z.string().optional(),
    unit_id: z.string().min(1, { message: 'Unit is required.' }),
    unit_label: z.string().optional(),
    quantity_before: z.coerce
        .number()
        .min(0, { message: 'Quantity before must be at least 0.' })
        .optional()
        .default(0),
    quantity_adjusted: z.coerce.number().refine((n) => n !== 0, {
        message: 'Quantity adjusted cannot be 0.',
    }),
    unit_cost: z.coerce
        .number()
        .min(0, { message: 'Unit cost must be at least 0.' })
        .optional()
        .default(0),
    reason: z.string().optional(),
});

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
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{item ? 'Edit Item' : 'Add Item'}</DialogTitle>
                    <DialogDescription className="sr-only">
                        {item
                            ? 'Edit stock adjustment item.'
                            : 'Add stock adjustment item.'}
                    </DialogDescription>
                </DialogHeader>

                <Form {...form}>
                    <form
                        onSubmit={(event) => {
                            event.stopPropagation();
                            form.handleSubmit(onSave)(event);
                        }}
                        className="space-y-4"
                    >
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <AsyncSelectField<{ name?: string }>
                                key={`product-${defaultValues.product_id || 'new'}-${open ? 'open' : 'closed'}`}
                                name="product_id"
                                label="Product"
                                url="/api/products"
                                placeholder="Select product"
                                initialLabel={defaultValues.product_label}
                                onItemSelect={(product) => {
                                    form.setValue(
                                        'product_label',
                                        product?.name || '',
                                        {
                                            shouldDirty: true,
                                        },
                                    );
                                }}
                            />
                            <AsyncSelectField<{ name?: string }>
                                key={`unit-${defaultValues.unit_id || 'new'}-${open ? 'open' : 'closed'}`}
                                name="unit_id"
                                label="Unit"
                                url="/api/units"
                                placeholder="Select unit"
                                initialLabel={defaultValues.unit_label}
                                onItemSelect={(unit) => {
                                    form.setValue(
                                        'unit_label',
                                        unit?.name || '',
                                        {
                                            shouldDirty: true,
                                        },
                                    );
                                }}
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

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => onOpenChange(false)}
                            >
                                Cancel
                            </Button>
                            <Button type="submit">
                                {item ? 'Update Item' : 'Save Item'}
                            </Button>
                        </DialogFooter>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    );
}
