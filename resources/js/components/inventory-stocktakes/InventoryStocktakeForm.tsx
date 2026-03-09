'use client';

import axios from '@/lib/axios';
import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo, useRef } from 'react';
import { Controller, useFieldArray, useForm } from 'react-hook-form';

import { AsyncSelect } from '@/components/common/AsyncSelect';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { type InventoryStocktake } from '@/types/inventory-stocktake';
import {
    inventoryStocktakeFormSchema,
    type InventoryStocktakeFormData,
} from '@/utils/schemas';

interface InventoryStocktakeFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    inventoryStocktake?: InventoryStocktake | null;
    item?: InventoryStocktake | null;
    entity?: InventoryStocktake | null;
    onSubmit: (data: InventoryStocktakeFormData) => void;
    isLoading?: boolean;
}

const getInventoryStocktakeFormDefaults = (
    inventoryStocktake?: InventoryStocktake | null,
): InventoryStocktakeFormData => {
    if (!inventoryStocktake) {
        return {
            stocktake_number: '',
            warehouse_id: '',
            stocktake_date: new Date(),
            status: 'draft',
            product_category_id: '',
            notes: '',
            items: [
                {
                    product_id: '',
                    unit_id: '',
                    system_quantity: 0,
                    counted_quantity: 0,
                    notes: '',
                },
            ],
        };
    }

    return {
        stocktake_number: inventoryStocktake.stocktake_number || '',
        warehouse_id: inventoryStocktake.warehouse?.id
            ? String(inventoryStocktake.warehouse.id)
            : '',
        stocktake_date: inventoryStocktake.stocktake_date
            ? new Date(inventoryStocktake.stocktake_date)
            : new Date(),
        status: inventoryStocktake.status,
        product_category_id: inventoryStocktake.product_category?.id
            ? String(inventoryStocktake.product_category.id)
            : '',
        notes: inventoryStocktake.notes || '',
        items: (inventoryStocktake.items || []).length
            ? (inventoryStocktake.items || []).map((item) => ({
                  product_id: item.product?.id ? String(item.product.id) : '',
                  unit_id: item.unit?.id ? String(item.unit.id) : '',
                  system_quantity: Number(item.system_quantity || 0),
                  counted_quantity:
                      item.counted_quantity === null ||
                      item.counted_quantity === undefined
                          ? 0
                          : Number(item.counted_quantity),
                  notes: item.notes || '',
              }))
            : [
                  {
                      product_id: '',
                      unit_id: '',
                      system_quantity: 0,
                      counted_quantity: 0,
                      notes: '',
                  },
              ],
    };
};

export const InventoryStocktakeForm = memo<InventoryStocktakeFormProps>(
    function InventoryStocktakeForm({
        open,
        onOpenChange,
        inventoryStocktake,
        item,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const activeStocktake = inventoryStocktake || item || entity;
        const loadedIdRef = useRef<number | null>(null);

        const defaultValues = useMemo(
            () => getInventoryStocktakeFormDefaults(activeStocktake),
            [activeStocktake],
        );

        const form = useForm<InventoryStocktakeFormData>({
            resolver: zodResolver(inventoryStocktakeFormSchema),
            defaultValues,
        });

        const { fields, append, remove } = useFieldArray({
            control: form.control,
            name: 'items',
        });

        useEffect(() => {
            form.reset(defaultValues);
        }, [form, defaultValues]);

        useEffect(() => {
            const loadDetail = async () => {
                if (!open) return;
                if (!activeStocktake?.id) return;
                if (loadedIdRef.current === activeStocktake.id) return;
                if (activeStocktake.items && activeStocktake.items.length > 0) {
                    loadedIdRef.current = activeStocktake.id;
                    return;
                }

                try {
                    const response = await axios.get(
                        `/api/inventory-stocktakes/${activeStocktake.id}`,
                    );
                    const data = response.data?.data ?? response.data;
                    form.reset(getInventoryStocktakeFormDefaults(data));
                    loadedIdRef.current = activeStocktake.id;
                } catch {
                    loadedIdRef.current = activeStocktake.id;
                }
            };

            loadDetail();
        }, [open, activeStocktake?.id, activeStocktake?.items, form]);

        return (
            <EntityForm<InventoryStocktakeFormData>
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    activeStocktake
                        ? 'Edit Inventory Stocktake'
                        : 'Add New Inventory Stocktake'
                }
                onSubmit={onSubmit}
                isLoading={isLoading}
                className="sm:max-w-[900px]"
            >
                <div className="space-y-6">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <InputField
                            name="stocktake_number"
                            label="Stocktake Number"
                            placeholder="Auto-generated if empty"
                        />
                        <SelectField
                            name="status"
                            label="Status"
                            options={[
                                { value: 'draft', label: 'Draft' },
                                { value: 'in_progress', label: 'In Progress' },
                                { value: 'completed', label: 'Completed' },
                                { value: 'cancelled', label: 'Cancelled' },
                            ]}
                            placeholder="Select status"
                        />
                        <Controller
                            control={form.control}
                            name="warehouse_id"
                            render={({ field }) => (
                                <div className="space-y-2">
                                    <div className="text-sm leading-none font-medium">
                                        Warehouse
                                    </div>
                                    <AsyncSelect
                                        value={
                                            field.value
                                                ? String(field.value)
                                                : undefined
                                        }
                                        onValueChange={field.onChange}
                                        url="/api/warehouses"
                                        placeholder="Select warehouse"
                                        label="Warehouse"
                                    />
                                </div>
                            )}
                        />
                        <Controller
                            control={form.control}
                            name="product_category_id"
                            render={({ field }) => (
                                <div className="space-y-2">
                                    <div className="text-sm leading-none font-medium">
                                        Product Category
                                    </div>
                                    <AsyncSelect
                                        value={
                                            field.value
                                                ? String(field.value)
                                                : undefined
                                        }
                                        onValueChange={field.onChange}
                                        url="/api/product-categories"
                                        placeholder="All categories"
                                        label="Product Category"
                                    />
                                </div>
                            )}
                        />
                        <DatePickerField
                            name="stocktake_date"
                            label="Stocktake Date"
                        />
                        <TextareaField
                            name="notes"
                            label="Notes"
                            placeholder="Notes"
                            rows={2}
                        />
                    </div>

                    <div className="flex items-center justify-between">
                        <div className="text-sm font-semibold">Items</div>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                append({
                                    product_id: '',
                                    unit_id: '',
                                    system_quantity: 0,
                                    counted_quantity: 0,
                                    notes: '',
                                })
                            }
                        >
                            Add Item
                        </Button>
                    </div>

                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[260px]">
                                        Product
                                    </TableHead>
                                    <TableHead className="w-[160px]">
                                        Unit
                                    </TableHead>
                                    <TableHead className="w-[140px]">
                                        System Qty
                                    </TableHead>
                                    <TableHead className="w-[140px]">
                                        Counted Qty
                                    </TableHead>
                                    <TableHead>Notes</TableHead>
                                    <TableHead className="w-[80px]"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {fields.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            No items.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    fields.map((field, index) => (
                                        <TableRow key={field.id}>
                                            <TableCell>
                                                <Controller
                                                    control={form.control}
                                                    name={`items.${index}.product_id`}
                                                    render={({
                                                        field: productField,
                                                    }) => (
                                                        <AsyncSelect
                                                            value={
                                                                productField.value
                                                                    ? String(
                                                                          productField.value,
                                                                      )
                                                                    : undefined
                                                            }
                                                            onValueChange={
                                                                productField.onChange
                                                            }
                                                            url="/api/products"
                                                            placeholder="Select product"
                                                            label="Product"
                                                        />
                                                    )}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Controller
                                                    control={form.control}
                                                    name={`items.${index}.unit_id`}
                                                    render={({
                                                        field: unitField,
                                                    }) => (
                                                        <AsyncSelect
                                                            value={
                                                                unitField.value
                                                                    ? String(
                                                                          unitField.value,
                                                                      )
                                                                    : undefined
                                                            }
                                                            onValueChange={
                                                                unitField.onChange
                                                            }
                                                            url="/api/units"
                                                            placeholder="Select unit"
                                                            label="Unit"
                                                        />
                                                    )}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Controller
                                                    control={form.control}
                                                    name={`items.${index}.system_quantity`}
                                                    render={({
                                                        field: qtyField,
                                                    }) => (
                                                        <InputField
                                                            name={qtyField.name}
                                                            label=""
                                                            type="number"
                                                            placeholder="0"
                                                            className="space-y-0"
                                                        />
                                                    )}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Controller
                                                    control={form.control}
                                                    name={`items.${index}.counted_quantity`}
                                                    render={({
                                                        field: qtyField,
                                                    }) => (
                                                        <InputField
                                                            name={qtyField.name}
                                                            label=""
                                                            type="number"
                                                            placeholder="0"
                                                            className="space-y-0"
                                                        />
                                                    )}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Controller
                                                    control={form.control}
                                                    name={`items.${index}.notes`}
                                                    render={({
                                                        field: notesField,
                                                    }) => (
                                                        <InputField
                                                            name={
                                                                notesField.name
                                                            }
                                                            label=""
                                                            placeholder="Notes"
                                                            className="space-y-0"
                                                        />
                                                    )}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    onClick={() =>
                                                        remove(index)
                                                    }
                                                    disabled={
                                                        fields.length === 1
                                                    }
                                                >
                                                    Remove
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </EntityForm>
        );
    },
);
