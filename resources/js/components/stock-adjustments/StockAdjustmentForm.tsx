'use client';

import axios from '@/lib/axios';
import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo, useRef } from 'react';
import { Controller, useFieldArray, useForm } from 'react-hook-form';
import { z } from 'zod';

import { AsyncSelect } from '@/components/common/AsyncSelect';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import {
    EntityFormItemActionsCell,
    EntityFormItemEmptyRow,
    EntityFormItemSectionHeader,
} from '@/components/common/EntityFormItemTable';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useTranslation } from '@/contexts/i18n-context';
import { useEntityFormItemDialog } from '@/hooks/useEntityFormItemDialog';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import { type StockAdjustment } from '@/types/stock-adjustment';
import { toDateInputValue } from '@/utils/date-format';
import {
    formatItemReference,
    omitItemDisplayLabels,
} from '@/utils/entity-form-item';
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
            adjustment_date: toDateInputValue(new Date()),
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
            ? toDateInputValue(stockAdjustment.adjustment_date)
            : toDateInputValue(new Date()),
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
        const { t } = useTranslation();
        const fl = (key: string, fallback: string) => {
            const translated = t(`form_fields.stock_adjustments.${key}`);
            return translated === `form_fields.stock_adjustments.${key}`
                ? fallback
                : translated;
        };

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
        const watchedItems = form.watch('items');
        const {
            isItemDialogOpen,
            item: selectedItem,
            handleCreateNewItem,
            handleEditItem,
            handleItemDialogOpenChange,
            handleSaveItem,
        } = useEntityFormItemDialog({
            items: watchedItems,
            appendItem: append,
            updateItem: update,
            mapEditingItem: normalizeStockAdjustmentItem,
        });

        const handleSubmit = (data: StockAdjustmentFormData) => {
            onSubmit({
                ...data,
                items: data.items.map(omitItemDisplayLabels),
            });
        };

        useResetFormOnDefaultValues(form, defaultValues);

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

            void loadDetail();
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
                        ? fl('edit_title', 'Edit Stock Adjustment')
                        : fl('add_title', 'Add New Stock Adjustment')
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
                className="sm:max-w-[1000px]"
            >
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <InputField
                        name="adjustment_number"
                        label={fl('adjustment_number', 'Adjustment Number')}
                        placeholder={fl(
                            'adjustment_number_placeholder',
                            'Auto-generated if empty',
                        )}
                    />
                    <SelectField
                        name="status"
                        label={fl('status', 'Status')}
                        options={[
                            {
                                value: 'draft',
                                label: fl('status_draft', 'Draft'),
                            },
                            {
                                value: 'pending_approval',
                                label: fl(
                                    'status_pending_approval',
                                    'Pending Approval',
                                ),
                            },
                            {
                                value: 'approved',
                                label: fl('status_approved', 'Approved'),
                            },
                            {
                                value: 'cancelled',
                                label: fl('status_cancelled', 'Cancelled'),
                            },
                        ]}
                        placeholder={fl('status_placeholder', 'Select status')}
                    />
                    <Controller
                        control={form.control}
                        name="warehouse_id"
                        render={({ field }) => (
                            <div className="space-y-2">
                                <div className="text-sm leading-none font-medium">
                                    {fl('warehouse', 'Warehouse')}
                                </div>
                                <AsyncSelect
                                    value={
                                        field.value
                                            ? String(field.value)
                                            : undefined
                                    }
                                    onValueChange={field.onChange}
                                    url="/api/warehouses"
                                    placeholder={fl(
                                        'warehouse_placeholder',
                                        'Select warehouse',
                                    )}
                                    label={fl('warehouse', 'Warehouse')}
                                />
                            </div>
                        )}
                    />
                    <SelectField
                        name="adjustment_type"
                        label={fl('adjustment_type', 'Adjustment Type')}
                        options={[
                            {
                                value: 'damage',
                                label: fl('adjustment_type_damage', 'Damage'),
                            },
                            {
                                value: 'expired',
                                label: fl('adjustment_type_expired', 'Expired'),
                            },
                            {
                                value: 'shrinkage',
                                label: fl(
                                    'adjustment_type_shrinkage',
                                    'Shrinkage',
                                ),
                            },
                            {
                                value: 'correction',
                                label: fl(
                                    'adjustment_type_correction',
                                    'Correction',
                                ),
                            },
                            {
                                value: 'stocktake_result',
                                label: fl(
                                    'adjustment_type_stocktake_result',
                                    'Stocktake Result',
                                ),
                            },
                            {
                                value: 'initial_stock',
                                label: fl(
                                    'adjustment_type_initial_stock',
                                    'Initial Stock',
                                ),
                            },
                            {
                                value: 'other',
                                label: fl('adjustment_type_other', 'Other'),
                            },
                        ]}
                        placeholder={fl(
                            'adjustment_type_placeholder',
                            'Select type',
                        )}
                    />
                    <DatePickerField
                        name="adjustment_date"
                        label={fl('adjustment_date', 'Adjustment Date')}
                    />
                    <Controller
                        control={form.control}
                        name="inventory_stocktake_id"
                        render={({ field }) => (
                            <div className="space-y-2">
                                <div className="text-sm leading-none font-medium">
                                    {fl(
                                        'inventory_stocktake',
                                        'Inventory Stocktake (Optional)',
                                    )}
                                </div>
                                <AsyncSelect
                                    value={
                                        field.value
                                            ? String(field.value)
                                            : undefined
                                    }
                                    onValueChange={field.onChange}
                                    url="/api/inventory-stocktakes"
                                    placeholder={fl(
                                        'inventory_stocktake_placeholder',
                                        'Select stocktake',
                                    )}
                                    label={fl(
                                        'inventory_stocktake_select',
                                        'Inventory Stocktake',
                                    )}
                                    labelFn={getInventoryStocktakeOptionLabel}
                                    valueFn={(it) => String(it.id)}
                                />
                            </div>
                        )}
                    />
                    <div className="md:col-span-2">
                        <TextareaField
                            name="notes"
                            label={fl('notes', 'Notes')}
                            placeholder={fl('notes_placeholder', 'Notes')}
                            rows={2}
                        />
                    </div>
                </div>

                <div className="mt-2 space-y-3 border-t pt-4">
                    <EntityFormItemSectionHeader
                        onAddItem={handleCreateNewItem}
                    />

                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[250px]">
                                        {fl('item_product', 'Product')}
                                    </TableHead>
                                    <TableHead className="w-[160px]">
                                        {fl('item_unit', 'Unit')}
                                    </TableHead>
                                    <TableHead className="w-[140px]">
                                        {fl('item_qty_before', 'Qty Before')}
                                    </TableHead>
                                    <TableHead className="w-[160px]">
                                        {fl(
                                            'item_qty_adjusted',
                                            'Qty Adjusted',
                                        )}
                                    </TableHead>
                                    <TableHead className="w-[140px]">
                                        {fl('item_unit_cost', 'Unit Cost')}
                                    </TableHead>
                                    <TableHead>
                                        {fl('item_reason', 'Reason')}
                                    </TableHead>
                                    <TableHead className="w-[120px] text-right">
                                        {fl('item_action', 'Action')}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {fields.length === 0 ? (
                                    <EntityFormItemEmptyRow
                                        colSpan={7}
                                        className="py-8 text-center text-muted-foreground"
                                    />
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
                                                <EntityFormItemActionsCell
                                                    index={index}
                                                    onEdit={handleEditItem}
                                                    onRemove={remove}
                                                />
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
                    onOpenChange={handleItemDialogOpenChange}
                    item={selectedItem}
                    onSave={handleSaveItem}
                />
            </EntityForm>
        );
    },
);
