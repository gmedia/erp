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
import { type PurchaseOrderFormData } from '@/types/purchase-order';

const purchaseOrderItemSchema = z.object({
    purchase_request_item_id: z.string().optional(),
    product_id: z.string().min(1, { message: 'Product is required.' }),
    product_label: z.string().optional(),
    unit_id: z.string().min(1, { message: 'Unit is required.' }),
    unit_label: z.string().optional(),
    quantity: z.coerce.number().gt(0, { message: 'Quantity must be greater than 0.' }),
    unit_price: z.coerce.number().min(0, { message: 'Unit price must be at least 0.' }),
    discount_percent: z.coerce.number().min(0).max(100).optional().default(0),
    tax_percent: z.coerce.number().min(0).max(100).optional().default(0),
    notes: z.string().optional(),
});

type PurchaseOrderItemFormData = PurchaseOrderFormData['items'][number];

interface PurchaseOrderItemFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    item: PurchaseOrderItemFormData | null;
    onSave: (data: PurchaseOrderItemFormData) => void;
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
                        {item ? 'Edit purchase order item.' : 'Add purchase order item.'}
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