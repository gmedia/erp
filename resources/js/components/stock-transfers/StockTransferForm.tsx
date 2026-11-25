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
import {
    ItemTableHeader,
    type ItemTableColumn,
} from '@/components/common/ItemTableHeader';
import { NotesTextareaField } from '@/components/common/NotesTextareaField';
import SelectField from '@/components/common/SelectField';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableRow } from '@/components/ui/table';
import { useTranslation } from '@/contexts/i18n-context';
import { useEntityFormItemDialog } from '@/hooks/useEntityFormItemDialog';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import { StockTransfer } from '@/types/stock-transfer';
import { toDateInputValue } from '@/utils/date-format';
import {
    formatItemReference,
    omitItemDisplayLabels,
} from '@/utils/entity-form-item';
import {
    stockTransferFormSchema,
    type StockTransferFormData,
} from '@/utils/schemas';
import { StockTransferItemFormDialog } from './StockTransferItemFormDialog';

type StockTransferFormInput = z.input<typeof stockTransferFormSchema>;

interface StockTransferFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    stockTransfer?: StockTransfer | null;
    item?: StockTransfer | null;
    entity?: StockTransfer | null;
    onSubmit: (data: StockTransferFormData) => void;
    isLoading?: boolean;
}

const buildStockTransferItemColumns = (
    qtyReceivedLabel: string,
): readonly ItemTableColumn[] => [
    { key: 'item_product', fallback: 'Product', className: 'w-[220px]' },
    { key: 'item_unit', fallback: 'Unit', className: 'w-[160px]' },
    { key: 'item_qty', fallback: 'Qty', className: 'w-[120px]' },
    {
        key: 'qty_received',
        fallback: '',
        className: 'w-[140px]',
        label: qtyReceivedLabel,
    },
    { key: 'item_unit_cost', fallback: 'Unit Cost', className: 'w-[140px]' },
    { key: 'item_notes', fallback: 'Notes' },
    {
        key: 'item_action',
        fallback: 'Action',
        className: 'w-[120px] text-right',
    },
];

const createEmptyStockTransferItem =
    (): StockTransferFormData['items'][number] => ({
        product_id: '',
        product_label: '',
        unit_id: '',
        unit_label: '',
        quantity: 1,
        quantity_received: null,
        unit_cost: 0,
        notes: '',
    });

const normalizeStockTransferItem = (
    item?: Partial<StockTransferFormInput['items'][number]> | null,
): StockTransferFormData['items'][number] => ({
    ...createEmptyStockTransferItem(),
    ...item,
    product_id: typeof item?.product_id === 'string' ? item.product_id : '',
    product_label:
        typeof item?.product_label === 'string' ? item.product_label : '',
    unit_id: typeof item?.unit_id === 'string' ? item.unit_id : '',
    unit_label: typeof item?.unit_label === 'string' ? item.unit_label : '',
    quantity: typeof item?.quantity === 'number' ? item.quantity : 1,
    quantity_received:
        typeof item?.quantity_received === 'number'
            ? item.quantity_received
            : null,
    unit_cost: typeof item?.unit_cost === 'number' ? item.unit_cost : 0,
    notes: typeof item?.notes === 'string' ? item.notes : '',
});

const getStockTransferFormDefaults = (
    stockTransfer?: StockTransfer | null,
): StockTransferFormData => {
    if (!stockTransfer) {
        return {
            transfer_number: '',
            from_warehouse_id: '',
            to_warehouse_id: '',
            transfer_date: toDateInputValue(new Date()),
            expected_arrival_date: null,
            status: 'draft',
            notes: '',
            requested_by: '',
            items: [],
        };
    }

    const items = stockTransfer.items ?? [];

    return {
        transfer_number: stockTransfer.transfer_number || '',
        from_warehouse_id: stockTransfer.from_warehouse?.id
            ? String(stockTransfer.from_warehouse.id)
            : '',
        to_warehouse_id: stockTransfer.to_warehouse?.id
            ? String(stockTransfer.to_warehouse.id)
            : '',
        transfer_date: stockTransfer.transfer_date
            ? toDateInputValue(stockTransfer.transfer_date)
            : toDateInputValue(new Date()),
        expected_arrival_date: stockTransfer.expected_arrival_date
            ? toDateInputValue(stockTransfer.expected_arrival_date)
            : null,
        status: stockTransfer.status,
        notes: stockTransfer.notes || '',
        requested_by: stockTransfer.requested_by?.id
            ? String(stockTransfer.requested_by.id)
            : '',
        items: items.length
            ? items.map((item) => ({
                  product_id: item.product?.id ? String(item.product.id) : '',
                  product_label: item.product?.name || '',
                  unit_id: item.unit?.id ? String(item.unit.id) : '',
                  unit_label: item.unit?.name || '',
                  quantity: Number(item.quantity || 0),
                  quantity_received:
                      item.quantity_received === null ||
                      item.quantity_received === undefined
                          ? null
                          : Number(item.quantity_received),
                  unit_cost: Number(item.unit_cost || 0),
                  notes: item.notes || '',
              }))
            : [],
    };
};

export const StockTransferForm = memo<StockTransferFormProps>(
    function StockTransferForm({
        open,
        onOpenChange,
        stockTransfer,
        item,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const { t } = useTranslation();
        const fl = (key: string, fallback: string) => {
            const translated = t(`form_fields.stock_transfers.${key}`);
            return translated === `form_fields.stock_transfers.${key}`
                ? fallback
                : translated;
        };
        const activeStockTransfer = stockTransfer || item || entity;
        const loadedIdRef = useRef<number | null>(null);
        const itemsLocked =
            activeStockTransfer?.status === 'in_transit' ||
            activeStockTransfer?.status === 'received' ||
            activeStockTransfer?.status === 'cancelled';

        const defaultValues = useMemo(
            () => getStockTransferFormDefaults(activeStockTransfer),
            [activeStockTransfer],
        );

        const form = useForm<
            StockTransferFormInput,
            unknown,
            StockTransferFormData
        >({
            resolver: zodResolver(stockTransferFormSchema),
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
            mapEditingItem: normalizeStockTransferItem,
        });

        const handleSubmit = (data: StockTransferFormData) => {
            onSubmit({
                ...data,
                items: data.items.map(omitItemDisplayLabels),
            });
        };

        useResetFormOnDefaultValues(form, defaultValues);

        useEffect(() => {
            const loadDetail = async () => {
                if (!open) return;
                if (!activeStockTransfer?.id) return;
                if (loadedIdRef.current === activeStockTransfer.id) return;
                if (
                    activeStockTransfer.items &&
                    activeStockTransfer.items.length > 0
                ) {
                    loadedIdRef.current = activeStockTransfer.id;
                    return;
                }

                try {
                    const response = await axios.get(
                        `/api/stock-transfers/${activeStockTransfer.id}`,
                    );
                    const data = response.data?.data ?? response.data;
                    form.reset(getStockTransferFormDefaults(data));
                    loadedIdRef.current = activeStockTransfer.id;
                } catch {
                    loadedIdRef.current = activeStockTransfer.id;
                }
            };

            void loadDetail();
        }, [open, activeStockTransfer?.id, activeStockTransfer?.items, form]);

        return (
            <EntityForm<StockTransferFormInput, StockTransferFormData>
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    activeStockTransfer
                        ? fl('edit_title', 'Edit Stock Transfer')
                        : fl('add_title', 'Add New Stock Transfer')
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
                className="sm:max-w-[900px]"
            >
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <InputField
                        name="transfer_number"
                        label={fl('transfer_number', 'Transfer Number')}
                        placeholder={fl(
                            'transfer_number_placeholder',
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
                                value: 'in_transit',
                                label: fl('status_in_transit', 'In Transit'),
                            },
                            {
                                value: 'received',
                                label: fl('status_received', 'Received'),
                            },
                            {
                                value: 'cancelled',
                                label: fl('status_cancelled', 'Cancelled'),
                            },
                        ]}
                        placeholder={fl('status_placeholder', 'Select status')}
                    />
                    <div className="grid grid-cols-1 gap-4 md:col-span-2 md:grid-cols-2">
                        <Controller
                            control={form.control}
                            name="from_warehouse_id"
                            render={({ field }) => (
                                <div className="space-y-2">
                                    <div className="text-sm leading-none font-medium">
                                        {fl('from_warehouse', 'From Warehouse')}
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
                                        label={fl(
                                            'from_warehouse',
                                            'From Warehouse',
                                        )}
                                    />
                                </div>
                            )}
                        />
                        <Controller
                            control={form.control}
                            name="to_warehouse_id"
                            render={({ field }) => (
                                <div className="space-y-2">
                                    <div className="text-sm leading-none font-medium">
                                        {fl('to_warehouse', 'To Warehouse')}
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
                                        label={fl(
                                            'to_warehouse',
                                            'To Warehouse',
                                        )}
                                    />
                                </div>
                            )}
                        />
                    </div>
                    <DatePickerField
                        name="transfer_date"
                        label={fl('transfer_date', 'Transfer Date')}
                    />
                    <DatePickerField
                        name="expected_arrival_date"
                        label={fl(
                            'expected_arrival_date',
                            'Expected Arrival Date',
                        )}
                    />
                    <Controller
                        control={form.control}
                        name="requested_by"
                        render={({ field }) => (
                            <div className="space-y-2">
                                <div className="text-sm leading-none font-medium">
                                    {fl(
                                        'requested_by',
                                        'Requested By (Employee)',
                                    )}
                                </div>
                                <AsyncSelect
                                    value={
                                        field.value
                                            ? String(field.value)
                                            : undefined
                                    }
                                    onValueChange={field.onChange}
                                    url="/api/employees"
                                    placeholder={fl(
                                        'requested_by_placeholder',
                                        'Select employee',
                                    )}
                                    label={fl(
                                        'requested_by_select',
                                        'Requested By',
                                    )}
                                />
                            </div>
                        )}
                    />
                    <NotesTextareaField
                        label={fl('notes', 'Notes')}
                        placeholder={fl(
                            'notes_placeholder',
                            'Additional notes...',
                        )}
                        rows={3}
                    />
                </div>

                <div className="mt-2 space-y-3 border-t pt-4">
                    {itemsLocked ? (
                        <div className="text-sm font-semibold">
                            {fl('items', 'Items')}
                        </div>
                    ) : (
                        <EntityFormItemSectionHeader
                            onAddItem={handleCreateNewItem}
                        />
                    )}

                    <div className="rounded-md border">
                        <Table>
                            <ItemTableHeader
                                columns={buildStockTransferItemColumns(
                                    t('stockTransfer.qty_received_label'),
                                )}
                                translate={fl}
                            />
                            <TableBody>
                                {fields.length === 0 ? (
                                    <EntityFormItemEmptyRow
                                        colSpan={7}
                                        className="py-8 text-center text-muted-foreground"
                                    />
                                ) : (
                                    fields.map((field, index) => {
                                        const stockTransferItem =
                                            normalizeStockTransferItem(
                                                watchedItems?.[index],
                                            );

                                        return (
                                            <TableRow key={field.id}>
                                                <TableCell>
                                                    {formatItemReference(
                                                        stockTransferItem.product_label,
                                                        stockTransferItem.product_id,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {formatItemReference(
                                                        stockTransferItem.unit_label,
                                                        stockTransferItem.unit_id,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {stockTransferItem.quantity ??
                                                        0}
                                                </TableCell>
                                                <TableCell>
                                                    {stockTransferItem.quantity_received ??
                                                        stockTransferItem.quantity ??
                                                        0}
                                                </TableCell>
                                                <TableCell>
                                                    {stockTransferItem.unit_cost ??
                                                        0}
                                                </TableCell>
                                                <TableCell>
                                                    {stockTransferItem.notes ||
                                                        '-'}
                                                </TableCell>
                                                {itemsLocked ? (
                                                    <TableCell className="text-right">
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() =>
                                                                handleEditItem(
                                                                    index,
                                                                )
                                                            }
                                                            aria-label={`Edit received qty item ${index + 1}`}
                                                        >
                                                            {t(
                                                                'stockTransfer.qty_received_label',
                                                            )}
                                                        </Button>
                                                    </TableCell>
                                                ) : (
                                                    <EntityFormItemActionsCell
                                                        index={index}
                                                        onEdit={handleEditItem}
                                                        onRemove={remove}
                                                    />
                                                )}
                                            </TableRow>
                                        );
                                    })
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </div>

                <StockTransferItemFormDialog
                    open={isItemDialogOpen}
                    onOpenChange={handleItemDialogOpenChange}
                    item={selectedItem}
                    onSave={handleSaveItem}
                    locked={itemsLocked}
                />
            </EntityForm>
        );
    },
);
