'use client';

import axios from '@/lib/axios';
import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo, useRef } from 'react';
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

interface StockAdjustmentFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    stockAdjustment?: StockAdjustment | null;
    item?: StockAdjustment | null;
    entity?: StockAdjustment | null;
    onSubmit: (data: StockAdjustmentFormData) => void;
    isLoading?: boolean;
}

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
            items: [
                {
                    product_id: '',
                    unit_id: '',
                    quantity_before: 0,
                    quantity_adjusted: 1,
                    unit_cost: 0,
                    reason: '',
                },
            ],
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
                  unit_id: it.unit?.id ? String(it.unit.id) : '',
                  quantity_before: Number(it.quantity_before || 0),
                  quantity_adjusted: Number(it.quantity_adjusted || 0),
                  unit_cost: Number(it.unit_cost || 0),
                  reason: it.reason || '',
              }))
            : [
                  {
                      product_id: '',
                      unit_id: '',
                      quantity_before: 0,
                      quantity_adjusted: 1,
                      unit_cost: 0,
                      reason: '',
                  },
              ],
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

        type StockAdjustmentFormInput = z.input<typeof stockAdjustmentFormSchema>;

        const form = useForm<
            StockAdjustmentFormInput,
            unknown,
            StockAdjustmentFormData
        >({
            resolver: zodResolver(stockAdjustmentFormSchema),
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
                onSubmit={onSubmit}
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
                                    labelFn={(it) =>
                                        String(it.stocktake_number ?? it.id ?? '')
                                    }
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
                            onClick={() =>
                                append({
                                    product_id: '',
                                    unit_id: '',
                                    quantity_before: 0,
                                    quantity_adjusted: 1,
                                    unit_cost: 0,
                                    reason: '',
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
                                    <TableHead className="w-[80px]"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {fields.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            No items.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    fields.map((f, index) => (
                                        <TableRow key={f.id}>
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
                                                    name={`items.${index}.quantity_before`}
                                                    render={({
                                                        field: qbField,
                                                    }) => (
                                                        <InputField
                                                            name={qbField.name}
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
                                                    name={`items.${index}.quantity_adjusted`}
                                                    render={({
                                                        field: qaField,
                                                    }) => (
                                                        <InputField
                                                            name={qaField.name}
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
                                                    name={`items.${index}.unit_cost`}
                                                    render={({
                                                        field: ucField,
                                                    }) => (
                                                        <InputField
                                                            name={ucField.name}
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
                                                    name={`items.${index}.reason`}
                                                    render={({
                                                        field: reasonField,
                                                    }) => (
                                                        <InputField
                                                            name={
                                                                reasonField.name
                                                            }
                                                            label=""
                                                            placeholder="Reason"
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
