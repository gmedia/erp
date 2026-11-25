'use client';

import axios from '@/lib/axios';
import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo, useRef } from 'react';
import { useFieldArray, useForm } from 'react-hook-form';
import { z } from 'zod';

import { ControlledAsyncSelectField } from '@/components/common/ControlledAsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import {
    EntityFormItemActionsCell,
    EntityFormItemEmptyRow,
    EntityFormItemSectionHeader,
} from '@/components/common/EntityFormItemTable';
import { InputField } from '@/components/common/InputField';
import {
    ItemTableHeader,
    type ItemTableColumn,
} from '@/components/common/ItemTableHeader';
import { NotesTextareaField } from '@/components/common/NotesTextareaField';
import SelectField from '@/components/common/SelectField';
import { StatusSelectField } from '@/components/common/StatusSelectField';
import { Table, TableBody, TableCell, TableRow } from '@/components/ui/table';
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

const ADJUSTMENT_STATUSES = [
    ['draft', 'Draft'],
    ['pending_approval', 'Pending Approval'],
    ['approved', 'Approved'],
    ['cancelled', 'Cancelled'],
] as const;

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

const STOCK_ADJUSTMENT_ITEM_COLUMNS: readonly ItemTableColumn[] = [
    { key: 'item_product', fallback: 'Product', className: 'w-[250px]' },
    { key: 'item_unit', fallback: 'Unit', className: 'w-[160px]' },
    { key: 'item_qty_before', fallback: 'Qty Before', className: 'w-[140px]' },
    {
        key: 'item_qty_adjusted',
        fallback: 'Qty Adjusted',
        className: 'w-[160px]',
    },
    { key: 'item_unit_cost', fallback: 'Unit Cost', className: 'w-[140px]' },
    { key: 'item_reason', fallback: 'Reason' },
    {
        key: 'item_action',
        fallback: 'Action',
        className: 'w-[120px] text-right',
    },
];

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
                    <StatusSelectField
                        fl={fl}
                        label={fl('status', 'Status')}
                        placeholder={fl('status_placeholder', 'Select status')}
                        statuses={ADJUSTMENT_STATUSES}
                    />
                    <ControlledAsyncSelectField
                        control={form.control}
                        name="warehouse_id"
                        label={fl('warehouse', 'Warehouse')}
                        url="/api/warehouses"
                        placeholder={fl(
                            'warehouse_placeholder',
                            'Select warehouse',
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
                    <ControlledAsyncSelectField
                        control={form.control}
                        name="inventory_stocktake_id"
                        label={fl(
                            'inventory_stocktake',
                            'Inventory Stocktake (Optional)',
                        )}
                        selectLabel={fl(
                            'inventory_stocktake_select',
                            'Inventory Stocktake',
                        )}
                        url="/api/inventory-stocktakes"
                        placeholder={fl(
                            'inventory_stocktake_placeholder',
                            'Select stocktake',
                        )}
                        labelFn={getInventoryStocktakeOptionLabel}
                        valueFn={(it) => String(it.id)}
                    />
                    <NotesTextareaField
                        label={fl('notes', 'Notes')}
                        placeholder={fl('notes_placeholder', 'Notes')}
                    />
                </div>

                <div className="mt-2 space-y-3 border-t pt-4">
                    <EntityFormItemSectionHeader
                        onAddItem={handleCreateNewItem}
                    />

                    <div className="rounded-md border">
                        <Table>
                            <ItemTableHeader
                                columns={STOCK_ADJUSTMENT_ITEM_COLUMNS}
                                translate={fl}
                            />
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
