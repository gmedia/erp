'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo, useRef } from 'react';
import { useFieldArray, useForm, type Resolver } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
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
import { Table, TableBody, TableCell, TableRow } from '@/components/ui/table';
import { useTranslation } from '@/contexts/i18n-context';
import { useEntityFormItemDialog } from '@/hooks/useEntityFormItemDialog';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import {
    type SupplierReturn,
    type SupplierReturnFormData,
} from '@/types/supplier-return';
import { toDateInputValue } from '@/utils/date-format';
import {
    formatItemReference,
    omitItemDisplayLabels,
} from '@/utils/entity-form-item';
import { supplierReturnFormSchema } from '@/utils/schemas';
import { SupplierReturnItemFormDialog } from './SupplierReturnItemFormDialog';

interface SupplierReturnFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    supplierReturn?: SupplierReturn | null;
    item?: SupplierReturn | null;
    entity?: SupplierReturn | null;
    onSubmit: (data: SupplierReturnFormData) => void;
    isLoading?: boolean;
}

const SUPPLIER_RETURN_ITEM_COLUMNS: readonly ItemTableColumn[] = [
    { key: 'item_product', fallback: 'Product', className: 'w-[220px]' },
    { key: 'item_unit', fallback: 'Unit', className: 'w-[140px]' },
    {
        key: 'item_qty_returned',
        fallback: 'Qty Returned',
        className: 'w-[120px]',
    },
    { key: 'item_unit_price', fallback: 'Unit Price', className: 'w-[140px]' },
    { key: 'item_notes', fallback: 'Notes' },
    {
        key: 'item_action',
        fallback: 'Action',
        className: 'w-[120px] text-right',
    },
];

const createEmptySupplierReturnItem =
    (): SupplierReturnFormData['items'][number] => ({
        goods_receipt_item_id: '',
        goods_receipt_item_label: '',
        product_id: '',
        product_label: '',
        unit_id: '',
        unit_label: '',
        quantity_returned: 1,
        unit_price: 0,
        notes: '',
    });

const getSupplierReturnFormDefaults = (
    supplierReturn?: SupplierReturn | null,
): SupplierReturnFormData => {
    if (!supplierReturn) {
        return {
            return_number: '',
            purchase_order_id: '',
            goods_receipt_id: '',
            supplier_id: '',
            warehouse_id: '',
            return_date: toDateInputValue(new Date()),
            reason: 'defective',
            status: 'draft',
            notes: '',
            items: [],
        };
    }

    return {
        return_number: supplierReturn.return_number || '',
        purchase_order_id: supplierReturn.purchase_order?.id
            ? String(supplierReturn.purchase_order.id)
            : '',
        goods_receipt_id: supplierReturn.goods_receipt?.id
            ? String(supplierReturn.goods_receipt.id)
            : '',
        supplier_id: supplierReturn.supplier?.id
            ? String(supplierReturn.supplier.id)
            : '',
        warehouse_id: supplierReturn.warehouse?.id
            ? String(supplierReturn.warehouse.id)
            : '',
        return_date: supplierReturn.return_date
            ? toDateInputValue(supplierReturn.return_date)
            : toDateInputValue(new Date()),
        reason: supplierReturn.reason,
        status: supplierReturn.status,
        notes: supplierReturn.notes || '',
        items: (supplierReturn.items || []).length
            ? (supplierReturn.items || []).map((it) => ({
                  goods_receipt_item_id: String(it.goods_receipt_item_id),
                  goods_receipt_item_label: it.product?.name || '',
                  product_id: it.product?.id ? String(it.product.id) : '',
                  product_label: it.product?.name || '',
                  unit_id: it.unit?.id ? String(it.unit.id) : '',
                  unit_label: it.unit?.name || '',
                  quantity_returned: Number(it.quantity_returned || 0),
                  unit_price: Number(it.unit_price || 0),
                  notes: it.notes || '',
              }))
            : [],
    };
};

export const SupplierReturnForm = memo<SupplierReturnFormProps>(
    function SupplierReturnForm({
        open,
        onOpenChange,
        supplierReturn,
        item,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const { t } = useTranslation();
        const fl = (key: string, fallback: string) => {
            const translated = t(`form_fields.supplier_returns.${key}`);
            return translated === `form_fields.supplier_returns.${key}`
                ? fallback
                : translated;
        };
        const activeSupplierReturn = supplierReturn || item || entity;
        const defaultValues = useMemo(
            () => getSupplierReturnFormDefaults(activeSupplierReturn),
            [activeSupplierReturn],
        );

        const form = useForm<
            SupplierReturnFormData,
            unknown,
            SupplierReturnFormData
        >({
            resolver: zodResolver(supplierReturnFormSchema) as Resolver<
                SupplierReturnFormData,
                unknown,
                SupplierReturnFormData
            >,
            defaultValues,
        });

        const { fields, append, remove, update } = useFieldArray({
            control: form.control,
            name: 'items',
        });
        const watchedItems = form.watch('items');
        const watchedPurchaseOrderId = form.watch('purchase_order_id');
        const watchedGoodsReceiptId = form.watch('goods_receipt_id');
        const headerSourceKey = `${watchedPurchaseOrderId}|${watchedGoodsReceiptId}`;
        const defaultHeaderSourceKey = `${defaultValues.purchase_order_id}|${defaultValues.goods_receipt_id}`;
        const previousHeaderSourceKeyRef = useRef(defaultHeaderSourceKey);
        useEffect(() => {
            if (previousHeaderSourceKeyRef.current === headerSourceKey) {
                return;
            }
            const isResetToDefaults =
                headerSourceKey === defaultHeaderSourceKey;
            previousHeaderSourceKeyRef.current = headerSourceKey;
            if (isResetToDefaults) {
                return;
            }
            remove();
        }, [headerSourceKey, defaultHeaderSourceKey, remove]);
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
        });

        const handleSubmit = (data: SupplierReturnFormData) => {
            onSubmit({
                ...data,
                items: data.items.map(omitItemDisplayLabels),
            });
        };

        useResetFormOnDefaultValues(form, defaultValues);

        return (
            <EntityForm<SupplierReturnFormData>
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    activeSupplierReturn
                        ? fl('edit_title', 'Edit Supplier Return')
                        : fl('add_title', 'Add New Supplier Return')
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
                className="sm:max-w-[1100px]"
            >
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <InputField
                        name="return_number"
                        label={fl('return_number', 'Return Number')}
                        placeholder={fl(
                            'return_number_placeholder',
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
                                value: 'confirmed',
                                label: fl('status_confirmed', 'Confirmed'),
                            },
                            {
                                value: 'cancelled',
                                label: fl('status_cancelled', 'Cancelled'),
                            },
                        ]}
                        placeholder={fl('status_placeholder', 'Select status')}
                    />

                    <AsyncSelectField<{ po_number: string }>
                        name="purchase_order_id"
                        label={fl('purchase_order', 'Purchase Order')}
                        url="/api/purchase-orders"
                        placeholder={fl(
                            'purchase_order_placeholder',
                            'Select purchase order',
                        )}
                        labelFn={(po) => po.po_number}
                    />

                    <AsyncSelectField<{ gr_number: string }>
                        name="goods_receipt_id"
                        label={fl('goods_receipt', 'Goods Receipt')}
                        url="/api/goods-receipts"
                        placeholder={fl(
                            'goods_receipt_placeholder',
                            'Select goods receipt',
                        )}
                        labelFn={(gr) => gr.gr_number}
                    />

                    <AsyncSelectField
                        name="supplier_id"
                        label={fl('supplier', 'Supplier')}
                        url="/api/suppliers"
                        placeholder={fl(
                            'supplier_placeholder',
                            'Select supplier',
                        )}
                    />

                    <AsyncSelectField
                        name="warehouse_id"
                        label={fl('warehouse', 'Warehouse')}
                        url="/api/warehouses"
                        placeholder={fl(
                            'warehouse_placeholder',
                            'Select warehouse',
                        )}
                    />

                    <DatePickerField
                        name="return_date"
                        label={fl('return_date', 'Return Date')}
                    />

                    <SelectField
                        name="reason"
                        label={fl('reason', 'Reason')}
                        options={[
                            {
                                value: 'defective',
                                label: fl('reason_defective', 'Defective'),
                            },
                            {
                                value: 'wrong_item',
                                label: fl('reason_wrong_item', 'Wrong Item'),
                            },
                            {
                                value: 'excess_quantity',
                                label: fl(
                                    'reason_excess_quantity',
                                    'Excess Quantity',
                                ),
                            },
                            {
                                value: 'damaged',
                                label: fl('reason_damaged', 'Damaged'),
                            },
                            {
                                value: 'other',
                                label: fl('reason_other', 'Other'),
                            },
                        ]}
                        placeholder={fl('reason_placeholder', 'Select reason')}
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
                                columns={SUPPLIER_RETURN_ITEM_COLUMNS}
                                translate={fl}
                            />
                            <TableBody>
                                {fields.map((field, index) => {
                                    const supplierReturnItem =
                                        watchedItems?.[index] ??
                                        createEmptySupplierReturnItem();

                                    return (
                                        <TableRow key={field.id}>
                                            <TableCell>
                                                {formatItemReference(
                                                    supplierReturnItem.product_label,
                                                    supplierReturnItem.product_id,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {formatItemReference(
                                                    supplierReturnItem.unit_label,
                                                    supplierReturnItem.unit_id,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {supplierReturnItem.quantity_returned ??
                                                    0}
                                            </TableCell>
                                            <TableCell>
                                                {supplierReturnItem.unit_price ??
                                                    0}
                                            </TableCell>
                                            <TableCell>
                                                {supplierReturnItem.notes ||
                                                    '-'}
                                            </TableCell>
                                            <EntityFormItemActionsCell
                                                index={index}
                                                onEdit={handleEditItem}
                                                onRemove={remove}
                                            />
                                        </TableRow>
                                    );
                                })}
                                {fields.length === 0 && (
                                    <EntityFormItemEmptyRow colSpan={6} />
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </div>

                <SupplierReturnItemFormDialog
                    open={isItemDialogOpen}
                    onOpenChange={handleItemDialogOpenChange}
                    item={selectedItem}
                    onSave={handleSaveItem}
                    goodsReceiptId={watchedGoodsReceiptId}
                    supplierReturnId={activeSupplierReturn?.id}
                />
            </EntityForm>
        );
    },
);
