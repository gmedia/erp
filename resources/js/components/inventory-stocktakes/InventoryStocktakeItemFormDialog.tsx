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
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{item ? 'Edit Item' : 'Add Item'}</DialogTitle>
                    <DialogDescription className="sr-only">
                        {item
                            ? 'Edit inventory stocktake item.'
                            : 'Add inventory stocktake item.'}
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
