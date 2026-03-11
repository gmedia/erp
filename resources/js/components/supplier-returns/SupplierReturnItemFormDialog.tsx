'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { useEffect, useMemo } from 'react';
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
import { type SupplierReturnFormData } from '@/types/supplier-return';

const supplierReturnItemSchema = z.object({
    goods_receipt_item_id: z.string().min(1, { message: 'GR item is required.' }),
    product_id: z.string().min(1, { message: 'Product is required.' }),
    product_label: z.string().optional(),
    unit_id: z.string().optional(),
    unit_label: z.string().optional(),
    quantity_returned: z.coerce.number().gt(0, { message: 'Quantity returned must be greater than 0.' }),
    unit_price: z.coerce.number().min(0, { message: 'Unit price must be at least 0.' }),
    notes: z.string().optional(),
});

type SupplierReturnItemFormData = SupplierReturnFormData['items'][number];

interface SupplierReturnItemFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    item: SupplierReturnItemFormData | null;
    onSave: (data: SupplierReturnItemFormData) => void;
}

export function SupplierReturnItemFormDialog({
    open,
    onOpenChange,
    item,
    onSave,
}: SupplierReturnItemFormDialogProps) {
    const defaultValues = useMemo<SupplierReturnItemFormData>(() => {
        if (!item) {
            return {
                goods_receipt_item_id: '',
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

    useEffect(() => {
        if (open) {
            form.reset(defaultValues);
        }
    }, [open, defaultValues, form]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{item ? 'Edit Item' : 'Add Item'}</DialogTitle>
                    <DialogDescription className="sr-only">
                        {item ? 'Edit supplier return item.' : 'Add supplier return item.'}
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
                            <InputField
                                name="goods_receipt_item_id"
                                label="GR Item ID"
                                type="number"
                                min={1}
                                step="1"
                                placeholder="1"
                            />
                            <AsyncSelectField<{ name?: string }>
                                key={`product-${defaultValues.product_id || 'new'}-${open ? 'open' : 'closed'}`}
                                name="product_id"
                                label="Product"
                                url="/api/products"
                                placeholder="Select product"
                                initialLabel={defaultValues.product_label}
                                onItemSelect={(product) => {
                                    form.setValue('product_label', product?.name || '', {
                                        shouldDirty: true,
                                    });
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
                                    form.setValue('unit_label', unit?.name || '', {
                                        shouldDirty: true,
                                    });
                                }}
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

                        <TextareaField
                            name="notes"
                            label="Notes"
                            placeholder="Item notes"
                            rows={3}
                        />

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                                Cancel
                            </Button>
                            <Button type="submit">{item ? 'Update Item' : 'Save Item'}</Button>
                        </DialogFooter>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    );
}