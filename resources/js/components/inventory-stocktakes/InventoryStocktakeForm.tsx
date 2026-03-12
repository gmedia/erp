'use client';

import axios from '@/lib/axios';
import { zodResolver } from '@hookform/resolvers/zod';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { memo, useEffect, useMemo, useRef, useState } from 'react';
import { Controller, useFieldArray, useForm } from 'react-hook-form';
import { z } from 'zod';

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
import { InventoryStocktakeItemFormDialog } from './InventoryStocktakeItemFormDialog';

interface InventoryStocktakeFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    inventoryStocktake?: InventoryStocktake | null;
    item?: InventoryStocktake | null;
    entity?: InventoryStocktake | null;
    onSubmit: (data: InventoryStocktakeFormData) => void;
    isLoading?: boolean;
}

const createEmptyInventoryStocktakeItem =
    (): InventoryStocktakeFormData['items'][number] => ({
        product_id: '',
        product_label: '',
        unit_id: '',
        unit_label: '',
        system_quantity: 0,
        counted_quantity: 0,
        notes: '',
    });

const formatItemReference = (label?: string, id?: string) => {
    if (label) {
        return label;
    }

    if (id) {
        return `#${id}`;
    }

    return '-';
};

const omitDisplayLabels = <
    T extends { product_label?: string; unit_label?: string },
>(
    item: T,
) => {
    const nextItem = { ...item };
    delete nextItem.product_label;
    delete nextItem.unit_label;

    return nextItem;
};

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
            items: [],
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
                  product_label: item.product?.name || '',
                  unit_id: item.unit?.id ? String(item.unit.id) : '',
                  unit_label: item.unit?.name || '',
                  system_quantity: Number(item.system_quantity || 0),
                  counted_quantity:
                      item.counted_quantity === null ||
                      item.counted_quantity === undefined
                          ? 0
                          : Number(item.counted_quantity),
                  notes: item.notes || '',
              }))
            : [],
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

        type InventoryStocktakeFormInput = z.input<
            typeof inventoryStocktakeFormSchema
        >;

        const form = useForm<
            InventoryStocktakeFormInput,
            unknown,
            InventoryStocktakeFormData
        >({
            resolver: zodResolver(inventoryStocktakeFormSchema),
            defaultValues,
        });

        const { fields, append, remove, update } = useFieldArray({
            control: form.control,
            name: 'items',
        });
        const [isItemDialogOpen, setIsItemDialogOpen] = useState(false);
        const [editingIndex, setEditingIndex] = useState<number | null>(null);
        const watchedItems = form.watch('items');

        const handleCreateNewItem = () => {
            setEditingIndex(null);
            setIsItemDialogOpen(true);
        };

        const handleEditItem = (index: number) => {
            setEditingIndex(index);
            setIsItemDialogOpen(true);
        };

        const handleSubmit = (data: InventoryStocktakeFormData) => {
            onSubmit({
                ...data,
                items: data.items.map(omitDisplayLabels),
            });
        };

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
            <EntityForm<InventoryStocktakeFormInput, InventoryStocktakeFormData>
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    activeStocktake
                        ? 'Edit Inventory Stocktake'
                        : 'Add New Inventory Stocktake'
                }
                onSubmit={handleSubmit}
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
                            onClick={handleCreateNewItem}
                        >
                            <Plus className="mr-2 h-4 w-4" />
                            Add Item
                        </Button>
                    </div>

                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[220px]">
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
                                    <TableHead className="w-[120px] text-right">
                                        Action
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {fields.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            No items added yet.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    fields.map((field, index) => {
                                        const stocktakeItem =
                                            watchedItems?.[index] ||
                                            createEmptyInventoryStocktakeItem();

                                        return (
                                            <TableRow key={field.id}>
                                                <TableCell>
                                                    {formatItemReference(
                                                        stocktakeItem.product_label,
                                                        stocktakeItem.product_id,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {formatItemReference(
                                                        stocktakeItem.unit_label,
                                                        stocktakeItem.unit_id,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {stocktakeItem.system_quantity ??
                                                        0}
                                                </TableCell>
                                                <TableCell>
                                                    {stocktakeItem.counted_quantity ??
                                                        0}
                                                </TableCell>
                                                <TableCell>
                                                    {stocktakeItem.notes || '-'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() =>
                                                            handleEditItem(
                                                                index,
                                                            )
                                                        }
                                                        title="Edit item"
                                                        aria-label={`Edit item ${index + 1}`}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() =>
                                                            remove(index)
                                                        }
                                                        title="Remove item"
                                                        aria-label={`Remove item ${index + 1}`}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </div>

                <InventoryStocktakeItemFormDialog
                    open={isItemDialogOpen}
                    onOpenChange={(nextOpen) => {
                        setIsItemDialogOpen(nextOpen);
                        if (nextOpen) {
                            return;
                        }

                        setEditingIndex(null);
                    }}
                    item={
                        editingIndex === null
                            ? null
                            : watchedItems?.[editingIndex] || null
                    }
                    onSave={(data) => {
                        if (editingIndex !== null) {
                            update(editingIndex, data);
                        } else {
                            append(data);
                        }
                        setIsItemDialogOpen(false);
                        setEditingIndex(null);
                    }}
                />
            </EntityForm>
        );
    },
);
