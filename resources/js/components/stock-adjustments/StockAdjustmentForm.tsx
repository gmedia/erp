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
import { type StockAdjustment } from '@/types/stock-adjustment';
import {
    stockAdjustmentFormSchema,
    type StockAdjustmentFormData,
} from '@/utils/schemas';
import { StockAdjustmentItemFormDialog } from './StockAdjustmentItemFormDialog';

type StockAdjustmentFormInput = z.input<typeof stockAdjustmentFormSchema>;

interface StockAdjustmentFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    stockAdjustment?: StockAdjustment | null;
    item?: StockAdjustment | null;
    entity?: StockAdjustment | null;
    onSubmit: (data: StockAdjustmentFormData) => void;
    isLoading?: boolean;
}

const createEmptyStockAdjustmentItem =
    (): StockAdjustmentFormData['items'][number] => ({
        product_id: '',
        product_label: '',
        unit_id: '',
        unit_label: '',
        quantity_before: 0,
        quantity_adjusted: 1,
        unit_cost: 0,
        reason: '',
    });

const normalizeStockAdjustmentItem = (
    item?: Partial<StockAdjustmentFormInput['items'][number]> | null,
): StockAdjustmentFormData['items'][number] => ({
    ...createEmptyStockAdjustmentItem(),
    ...item,
    product_id: typeof item?.product_id === 'string' ? item.product_id : '',
    product_label:
        typeof item?.product_label === 'string' ? item.product_label : '',
    unit_id: typeof item?.unit_id === 'string' ? item.unit_id : '',
    unit_label: typeof item?.unit_label === 'string' ? item.unit_label : '',
    quantity_before:
        typeof item?.quantity_before === 'number' ? item.quantity_before : 0,
    quantity_adjusted:
        typeof item?.quantity_adjusted === 'number'
            ? item.quantity_adjusted
            : 1,
    unit_cost: typeof item?.unit_cost === 'number' ? item.unit_cost : 0,
    reason: typeof item?.reason === 'string' ? item.reason : '',
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

const getInventoryStocktakeOptionLabel = (option: Record<string, unknown>) => {
    const stocktakeNumber = option.stocktake_number;

    if (
        typeof stocktakeNumber === 'string' ||
        typeof stocktakeNumber === 'number'
    ) {
        return String(stocktakeNumber);
    }

    const optionId = option.id;

    if (typeof optionId === 'string' || typeof optionId === 'number') {
        return String(optionId);
    }

    return '';
};

const getStockAdjustmentFormDefaults = (
    stockAdjustment?: StockAdjustment | null,
): StockAdjustmentFormData => {
    if (!stockAdjustment) {
        return {
            adjustment_number: '',
            warehouse_id: '',
            adjustment_date: new Date(),
            adjustment_type: 'correction',
            status: 'draft',
            inventory_stocktake_id: '',
            notes: '',
            items: [],
        };
    }

    return {
        adjustment_number: stockAdjustment.adjustment_number || '',
        warehouse_id: stockAdjustment.warehouse?.id
            ? String(stockAdjustment.warehouse.id)
            : '',
        adjustment_date: stockAdjustment.adjustment_date
            ? new Date(stockAdjustment.adjustment_date)
            : new Date(),
        adjustment_type: stockAdjustment.adjustment_type,
        status: stockAdjustment.status,
        inventory_stocktake_id: stockAdjustment.inventory_stocktake?.id
            ? String(stockAdjustment.inventory_stocktake.id)
            : '',
        notes: stockAdjustment.notes || '',
        items: (stockAdjustment.items || []).length
            ? (stockAdjustment.items || []).map((it) => ({
                  product_id: it.product?.id ? String(it.product.id) : '',
                  product_label: it.product?.name || '',
                  unit_id: it.unit?.id ? String(it.unit.id) : '',
                  unit_label: it.unit?.name || '',
                  quantity_before: Number(it.quantity_before || 0),
                  quantity_adjusted: Number(it.quantity_adjusted || 0),
                  unit_cost: Number(it.unit_cost || 0),
                  reason: it.reason || '',
              }))
            : [],
    };
};

export const StockAdjustmentForm = memo<StockAdjustmentFormProps>(
    function StockAdjustmentForm({
        open,
        onOpenChange,
        stockAdjustment,
        item,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const activeStockAdjustment = stockAdjustment || item || entity;
        const loadedIdRef = useRef<number | null>(null);

        const defaultValues = useMemo(
            () => getStockAdjustmentFormDefaults(activeStockAdjustment),
            [activeStockAdjustment],
        );

        const form = useForm<
            StockAdjustmentFormInput,
            unknown,
            StockAdjustmentFormData
        >({
            resolver: zodResolver(stockAdjustmentFormSchema),
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

        const handleSubmit = (data: StockAdjustmentFormData) => {
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
                if (!activeStockAdjustment?.id) return;
                if (loadedIdRef.current === activeStockAdjustment.id) return;
                if (
                    activeStockAdjustment.items &&
                    activeStockAdjustment.items.length > 0
                ) {
                    loadedIdRef.current = activeStockAdjustment.id;
                    return;
                }

                try {
                    const response = await axios.get(
                        `/api/stock-adjustments/${activeStockAdjustment.id}`,
                    );
                    const data = response.data?.data ?? response.data;
                    form.reset(getStockAdjustmentFormDefaults(data));
                    loadedIdRef.current = activeStockAdjustment.id;
                } catch {
                    loadedIdRef.current = activeStockAdjustment.id;
                }
            };

            loadDetail();
        }, [
            open,
            activeStockAdjustment?.id,
            activeStockAdjustment?.items,
            form,
        ]);

        return (
            <EntityForm<StockAdjustmentFormInput, StockAdjustmentFormData>
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    activeStockAdjustment
                        ? 'Edit Stock Adjustment'
                        : 'Add New Stock Adjustment'
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
                className="sm:max-w-[1000px]"
            >
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <InputField
                        name="adjustment_number"
                        label="Adjustment Number"
                        placeholder="Auto-generated if empty"
                    />
                    <SelectField
                        name="status"
                        label="Status"
                        options={[
                            { value: 'draft', label: 'Draft' },
                            {
                                value: 'pending_approval',
                                label: 'Pending Approval',
                            },
                            { value: 'approved', label: 'Approved' },
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
                    <SelectField
                        name="adjustment_type"
                        label="Adjustment Type"
                        options={[
                            { value: 'damage', label: 'Damage' },
                            { value: 'expired', label: 'Expired' },
                            { value: 'shrinkage', label: 'Shrinkage' },
                            { value: 'correction', label: 'Correction' },
                            {
                                value: 'stocktake_result',
                                label: 'Stocktake Result',
                            },
                            { value: 'initial_stock', label: 'Initial Stock' },
                            { value: 'other', label: 'Other' },
                        ]}
                        placeholder="Select type"
                    />
                    <DatePickerField
                        name="adjustment_date"
                        label="Adjustment Date"
                    />
                    <Controller
                        control={form.control}
                        name="inventory_stocktake_id"
                        render={({ field }) => (
                            <div className="space-y-2">
                                <div className="text-sm leading-none font-medium">
                                    Inventory Stocktake (Optional)
                                </div>
                                <AsyncSelect
                                    value={
                                        field.value
                                            ? String(field.value)
                                            : undefined
                                    }
                                    onValueChange={field.onChange}
                                    url="/api/inventory-stocktakes"
                                    placeholder="Select stocktake"
                                    label="Inventory Stocktake"
                                    labelFn={getInventoryStocktakeOptionLabel}
                                    valueFn={(it) => String(it.id)}
                                />
                            </div>
                        )}
                    />
                    <div className="md:col-span-2">
                        <TextareaField
                            name="notes"
                            label="Notes"
                            placeholder="Notes"
                            rows={2}
                        />
                    </div>
                </div>

                <div className="mt-2 space-y-3 border-t pt-4">
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
                                    <TableHead className="w-[250px]">
                                        Product
                                    </TableHead>
                                    <TableHead className="w-[160px]">
                                        Unit
                                    </TableHead>
                                    <TableHead className="w-[140px]">
                                        Qty Before
                                    </TableHead>
                                    <TableHead className="w-[160px]">
                                        Qty Adjusted
                                    </TableHead>
                                    <TableHead className="w-[140px]">
                                        Unit Cost
                                    </TableHead>
                                    <TableHead>Reason</TableHead>
                                    <TableHead className="w-[120px] text-right">
                                        Action
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {fields.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            No items added yet.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    fields.map((f, index) => {
                                        const adjustmentItem =
                                            normalizeStockAdjustmentItem(
                                                watchedItems?.[index],
                                            );

                                        return (
                                            <TableRow key={f.id}>
                                                <TableCell>
                                                    {formatItemReference(
                                                        adjustmentItem.product_label,
                                                        adjustmentItem.product_id,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {formatItemReference(
                                                        adjustmentItem.unit_label,
                                                        adjustmentItem.unit_id,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {adjustmentItem.quantity_before ??
                                                        0}
                                                </TableCell>
                                                <TableCell>
                                                    {adjustmentItem.quantity_adjusted ??
                                                        0}
                                                </TableCell>
                                                <TableCell>
                                                    {adjustmentItem.unit_cost ??
                                                        0}
                                                </TableCell>
                                                <TableCell>
                                                    {adjustmentItem.reason ||
                                                        '-'}
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

                <StockAdjustmentItemFormDialog
                    open={isItemDialogOpen}
                    onOpenChange={(nextOpen) => {
                        setIsItemDialogOpen(nextOpen);
                        if (nextOpen) {
                            return;
                        }

                        setEditingIndex(null);
                    }}
                    item={
                        editingIndex !== null
                            ? normalizeStockAdjustmentItem(
                                  watchedItems?.[editingIndex],
                              )
                            : null
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
