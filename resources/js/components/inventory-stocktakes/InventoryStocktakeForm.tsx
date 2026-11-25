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
import { StatusSelectField } from '@/components/common/StatusSelectField';
import { Table, TableBody, TableCell, TableRow } from '@/components/ui/table';
import { useTranslation } from '@/contexts/i18n-context';
import { useEntityFormItemDialog } from '@/hooks/useEntityFormItemDialog';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import { type InventoryStocktake } from '@/types/inventory-stocktake';
import { toDateInputValue } from '@/utils/date-format';
import {
    formatItemReference,
    omitItemDisplayLabels,
} from '@/utils/entity-form-item';
import {
    inventoryStocktakeFormSchema,
    type InventoryStocktakeFormData,
} from '@/utils/schemas';
import { InventoryStocktakeItemFormDialog } from './InventoryStocktakeItemFormDialog';

const STOCKTAKE_STATUSES = [
    ['draft', 'Draft'],
    ['in_progress', 'In Progress'],
    ['completed', 'Completed'],
    ['cancelled', 'Cancelled'],
] as const;

type InventoryStocktakeFormInput = z.input<typeof inventoryStocktakeFormSchema>;

interface InventoryStocktakeFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    inventoryStocktake?: InventoryStocktake | null;
    item?: InventoryStocktake | null;
    entity?: InventoryStocktake | null;
    onSubmit: (data: InventoryStocktakeFormData) => void;
    isLoading?: boolean;
}

const INVENTORY_STOCKTAKE_ITEM_COLUMNS: readonly ItemTableColumn[] = [
    { key: 'item_product', fallback: 'Product', className: 'w-[220px]' },
    { key: 'item_unit', fallback: 'Unit', className: 'w-[160px]' },
    { key: 'item_system_qty', fallback: 'System Qty', className: 'w-[140px]' },
    {
        key: 'item_counted_qty',
        fallback: 'Counted Qty',
        className: 'w-[140px]',
    },
    { key: 'item_notes', fallback: 'Notes' },
    {
        key: 'item_action',
        fallback: 'Action',
        className: 'w-[120px] text-right',
    },
];

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

const normalizeInventoryStocktakeItem = (
    item?: Partial<InventoryStocktakeFormInput['items'][number]> | null,
): InventoryStocktakeFormData['items'][number] => ({
    ...createEmptyInventoryStocktakeItem(),
    ...item,
    product_id: typeof item?.product_id === 'string' ? item.product_id : '',
    product_label:
        typeof item?.product_label === 'string' ? item.product_label : '',
    unit_id: typeof item?.unit_id === 'string' ? item.unit_id : '',
    unit_label: typeof item?.unit_label === 'string' ? item.unit_label : '',
    system_quantity:
        typeof item?.system_quantity === 'number' ? item.system_quantity : 0,
    counted_quantity:
        typeof item?.counted_quantity === 'number' ? item.counted_quantity : 0,
    notes: typeof item?.notes === 'string' ? item.notes : '',
});

const getInventoryStocktakeFormDefaults = (
    inventoryStocktake?: InventoryStocktake | null,
): InventoryStocktakeFormData => {
    if (!inventoryStocktake) {
        return {
            stocktake_number: '',
            warehouse_id: '',
            stocktake_date: toDateInputValue(new Date()),
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
            ? toDateInputValue(inventoryStocktake.stocktake_date)
            : toDateInputValue(new Date()),
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
        const { t } = useTranslation();
        const fl = (key: string, fallback: string) => {
            const translated = t(`form_fields.inventory_stocktakes.${key}`);
            return translated === `form_fields.inventory_stocktakes.${key}`
                ? fallback
                : translated;
        };

        const defaultValues = useMemo(
            () => getInventoryStocktakeFormDefaults(activeStocktake),
            [activeStocktake],
        );

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
            mapEditingItem: normalizeInventoryStocktakeItem,
        });

        const handleSubmit = (data: InventoryStocktakeFormData) => {
            onSubmit({
                ...data,
                items: data.items.map(omitItemDisplayLabels),
            });
        };

        useResetFormOnDefaultValues(form, defaultValues);

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

            void loadDetail();
        }, [open, activeStocktake?.id, activeStocktake?.items, form]);

        return (
            <EntityForm<InventoryStocktakeFormInput, InventoryStocktakeFormData>
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    activeStocktake
                        ? fl('edit_title', 'Edit Inventory Stocktake')
                        : fl('add_title', 'Add New Inventory Stocktake')
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
                className="sm:max-w-[900px]"
            >
                <div className="space-y-6">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <InputField
                            name="stocktake_number"
                            label={fl('stocktake_number', 'Stocktake Number')}
                            placeholder={fl(
                                'stocktake_number_placeholder',
                                'Auto-generated if empty',
                            )}
                        />
                        <StatusSelectField
                            fl={fl}
                            label={fl('status', 'Status')}
                            placeholder={fl(
                                'status_placeholder',
                                'Select status',
                            )}
                            statuses={STOCKTAKE_STATUSES}
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
                        <ControlledAsyncSelectField
                            control={form.control}
                            name="product_category_id"
                            label={fl('product_category', 'Product Category')}
                            url="/api/product-categories"
                            placeholder={fl(
                                'product_category_placeholder',
                                'All categories',
                            )}
                        />
                        <DatePickerField
                            name="stocktake_date"
                            label={fl('stocktake_date', 'Stocktake Date')}
                        />
                        <NotesTextareaField
                            label={fl('notes', 'Notes')}
                            placeholder={fl('notes_placeholder', 'Notes')}
                            spanTwoColumns={false}
                        />
                    </div>

                    <EntityFormItemSectionHeader
                        onAddItem={handleCreateNewItem}
                    />

                    <div className="rounded-md border">
                        <Table>
                            <ItemTableHeader
                                columns={INVENTORY_STOCKTAKE_ITEM_COLUMNS}
                                translate={fl}
                            />
                            <TableBody>
                                {fields.length === 0 ? (
                                    <EntityFormItemEmptyRow
                                        colSpan={6}
                                        className="py-8 text-center text-muted-foreground"
                                    />
                                ) : (
                                    fields.map((field, index) => {
                                        const stocktakeItem =
                                            normalizeInventoryStocktakeItem(
                                                watchedItems?.[index],
                                            );

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

                <InventoryStocktakeItemFormDialog
                    open={isItemDialogOpen}
                    onOpenChange={handleItemDialogOpenChange}
                    item={selectedItem}
                    onSave={handleSaveItem}
                />
            </EntityForm>
        );
    },
);
