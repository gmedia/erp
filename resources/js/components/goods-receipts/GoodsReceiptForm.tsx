'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo, useRef, useState } from 'react';
import { useFieldArray, useForm, type Resolver } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import { DocumentNumberField } from '@/components/common/DocumentNumberField';
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
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Table, TableBody, TableCell, TableRow } from '@/components/ui/table';
import { useTranslation } from '@/contexts/i18n-context';
import { useEntityFormItemDialog } from '@/hooks/useEntityFormItemDialog';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import {
    type GoodsReceipt,
    type GoodsReceiptFormData,
} from '@/types/goods-receipt';
import { toDateInputValue } from '@/utils/date-format';
import {
    formatItemReference,
    omitItemDisplayLabels,
} from '@/utils/entity-form-item';
import { goodsReceiptFormSchema } from '@/utils/schemas';
import { GoodsReceiptItemFormDialog } from './GoodsReceiptItemFormDialog';

interface GoodsReceiptFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    goodsReceipt?: GoodsReceipt | null;
    item?: GoodsReceipt | null;
    entity?: GoodsReceipt | null;
    onSubmit: (data: GoodsReceiptFormData) => void;
    isLoading?: boolean;
}

const CONFIRMABLE_PO_STATUSES = ['confirmed', 'partially_received'];

const GOODS_RECEIPT_STATUSES = [
    ['draft', 'Draft'],
    ['confirmed', 'Confirmed'],
    ['cancelled', 'Cancelled'],
] as const;

const GOODS_RECEIPT_ITEM_COLUMNS: readonly ItemTableColumn[] = [
    { key: 'item_product', fallback: 'Product', className: 'w-[220px]' },
    { key: 'item_unit', fallback: 'Unit', className: 'w-[140px]' },
    {
        key: 'item_qty_received',
        fallback: 'Qty Received',
        className: 'w-[120px]',
    },
    {
        key: 'item_qty_accepted',
        fallback: 'Qty Accepted',
        className: 'w-[120px]',
    },
    {
        key: 'item_qty_rejected',
        fallback: 'Qty Rejected',
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

type PurchaseOrderOption = { po_number: string; status?: string };

const createEmptyGoodsReceiptItem =
    (): GoodsReceiptFormData['items'][number] => ({
        purchase_order_item_id: '',
        purchase_order_item_label: '',
        product_id: '',
        product_label: '',
        unit_id: '',
        unit_label: '',
        quantity_received: 1,
        quantity_accepted: 1,
        quantity_rejected: 0,
        unit_price: 0,
        notes: '',
    });

const getGoodsReceiptFormDefaults = (
    goodsReceipt?: GoodsReceipt | null,
): GoodsReceiptFormData => {
    if (!goodsReceipt) {
        return {
            gr_number: '',
            purchase_order_id: '',
            warehouse_id: '',
            receipt_date: toDateInputValue(new Date()),
            supplier_delivery_note: '',
            status: 'draft',
            received_by: '',
            notes: '',
            items: [],
        };
    }

    return {
        gr_number: goodsReceipt.gr_number || '',
        purchase_order_id: goodsReceipt.purchase_order?.id
            ? String(goodsReceipt.purchase_order.id)
            : '',
        warehouse_id: goodsReceipt.warehouse?.id
            ? String(goodsReceipt.warehouse.id)
            : '',
        receipt_date: goodsReceipt.receipt_date
            ? toDateInputValue(goodsReceipt.receipt_date)
            : toDateInputValue(new Date()),
        supplier_delivery_note: goodsReceipt.supplier_delivery_note || '',
        status: goodsReceipt.status,
        received_by: goodsReceipt.received_by?.id
            ? String(goodsReceipt.received_by.id)
            : '',
        notes: goodsReceipt.notes || '',
        items: (goodsReceipt.items || []).length
            ? (goodsReceipt.items || []).map((it) => ({
                  purchase_order_item_id: String(it.purchase_order_item_id),
                  purchase_order_item_label: it.product?.name || '',
                  product_id: it.product?.id ? String(it.product.id) : '',
                  product_label: it.product?.name || '',
                  unit_id: it.unit?.id ? String(it.unit.id) : '',
                  unit_label: it.unit?.name || '',
                  quantity_received: Number(it.quantity_received || 0),
                  quantity_accepted: Number(it.quantity_accepted || 0),
                  quantity_rejected: Number(it.quantity_rejected || 0),
                  unit_price: Number(it.unit_price || 0),
                  notes: it.notes || '',
              }))
            : [],
    };
};

export const GoodsReceiptForm = memo<GoodsReceiptFormProps>(
    function GoodsReceiptForm({
        open,
        onOpenChange,
        goodsReceipt,
        item,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const { t } = useTranslation();
        const fl = (key: string, fallback: string) => {
            const translated = t(`form_fields.goods_receipts.${key}`);
            return translated === `form_fields.goods_receipts.${key}`
                ? fallback
                : translated;
        };
        const activeGoodsReceipt = goodsReceipt || item || entity;
        const defaultValues = useMemo(
            () => getGoodsReceiptFormDefaults(activeGoodsReceipt),
            [activeGoodsReceipt],
        );

        const form = useForm<
            GoodsReceiptFormData,
            unknown,
            GoodsReceiptFormData
        >({
            resolver: zodResolver(goodsReceiptFormSchema) as Resolver<
                GoodsReceiptFormData,
                unknown,
                GoodsReceiptFormData
            >,
            defaultValues,
        });

        const { fields, append, remove, update } = useFieldArray({
            control: form.control,
            name: 'items',
        });
        const watchedItems = form.watch('items');
        const watchedPurchaseOrderId = form.watch('purchase_order_id');
        const watchedStatus = form.watch('status');
        const [selectedPoStatus, setSelectedPoStatus] = useState<
            string | undefined
        >(activeGoodsReceipt?.purchase_order?.status ?? undefined);
        const isNewlyConfirming =
            watchedStatus === 'confirmed' &&
            activeGoodsReceipt?.status !== 'confirmed';
        const isPoNotConfirmable =
            isNewlyConfirming &&
            !!watchedPurchaseOrderId &&
            !!selectedPoStatus &&
            !CONFIRMABLE_PO_STATUSES.includes(selectedPoStatus);
        const purchaseOrderListUrl =
            watchedStatus === 'confirmed'
                ? `/api/purchase-orders?${CONFIRMABLE_PO_STATUSES.map((s) => `status[]=${s}`).join('&')}`
                : '/api/purchase-orders';
        const previousPurchaseOrderIdRef = useRef(
            defaultValues.purchase_order_id,
        );
        useEffect(() => {
            if (previousPurchaseOrderIdRef.current === watchedPurchaseOrderId) {
                return;
            }
            const isResetToDefaults =
                watchedPurchaseOrderId === defaultValues.purchase_order_id;
            previousPurchaseOrderIdRef.current = watchedPurchaseOrderId;
            if (isResetToDefaults) {
                return;
            }
            remove();
        }, [watchedPurchaseOrderId, defaultValues.purchase_order_id, remove]);
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

        const handleSubmit = (data: GoodsReceiptFormData) => {
            if (
                data.status === 'confirmed' &&
                selectedPoStatus &&
                !CONFIRMABLE_PO_STATUSES.includes(selectedPoStatus)
            ) {
                form.setError('purchase_order_id', {
                    type: 'validate',
                    message: t('goods_receipt.po_not_approved'),
                });
                return;
            }
            onSubmit({
                ...data,
                items: data.items.map(omitItemDisplayLabels),
            });
        };

        useResetFormOnDefaultValues(form, defaultValues);

        return (
            <EntityForm<GoodsReceiptFormData>
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    activeGoodsReceipt
                        ? fl('edit_title', 'Edit Goods Receipt')
                        : fl('add_title', 'Add New Goods Receipt')
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
                className="sm:max-w-[1100px]"
            >
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <DocumentNumberField
                        fl={fl}
                        name="gr_number"
                        label={fl('gr_number', 'GR Number')}
                        placeholderKey="gr_number_placeholder"
                    />

                    <StatusSelectField
                        fl={fl}
                        label={fl('status', 'Status')}
                        placeholder={fl('status_placeholder', 'Select status')}
                        statuses={GOODS_RECEIPT_STATUSES}
                    />

                    <div className="space-y-2">
                        <AsyncSelectField<PurchaseOrderOption>
                            name="purchase_order_id"
                            label={fl('purchase_order', 'Purchase Order')}
                            url={purchaseOrderListUrl}
                            placeholder={fl(
                                'purchase_order_placeholder',
                                'Select purchase order',
                            )}
                            labelFn={(po) => po.po_number}
                            onItemSelect={(po) =>
                                setSelectedPoStatus(po.status)
                            }
                        />
                        {isPoNotConfirmable && (
                            <Alert variant="destructive">
                                <AlertDescription>
                                    {t('goods_receipt.po_not_approved')}
                                </AlertDescription>
                            </Alert>
                        )}
                    </div>

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
                        name="receipt_date"
                        label={fl('receipt_date', 'Receipt Date')}
                    />

                    <AsyncSelectField
                        name="received_by"
                        label={fl('received_by', 'Received By')}
                        url="/api/employees"
                        placeholder={fl(
                            'received_by_placeholder',
                            'Select employee',
                        )}
                    />

                    <InputField
                        name="supplier_delivery_note"
                        label={fl(
                            'supplier_delivery_note',
                            'Supplier Delivery Note',
                        )}
                        placeholder="SJ-0001"
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
                                columns={GOODS_RECEIPT_ITEM_COLUMNS}
                                translate={fl}
                            />
                            <TableBody>
                                {fields.map((field, index) => {
                                    const goodsReceiptItem =
                                        watchedItems?.[index] ??
                                        createEmptyGoodsReceiptItem();

                                    return (
                                        <TableRow key={field.id}>
                                            <TableCell>
                                                {formatItemReference(
                                                    goodsReceiptItem.product_label,
                                                    goodsReceiptItem.product_id,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {formatItemReference(
                                                    goodsReceiptItem.unit_label,
                                                    goodsReceiptItem.unit_id,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {goodsReceiptItem.quantity_received ??
                                                    0}
                                            </TableCell>
                                            <TableCell>
                                                {goodsReceiptItem.quantity_accepted ??
                                                    0}
                                            </TableCell>
                                            <TableCell>
                                                {goodsReceiptItem.quantity_rejected ??
                                                    0}
                                            </TableCell>
                                            <TableCell>
                                                {goodsReceiptItem.unit_price ??
                                                    0}
                                            </TableCell>
                                            <TableCell>
                                                {goodsReceiptItem.notes || '-'}
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
                                    <EntityFormItemEmptyRow colSpan={8} />
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </div>

                <GoodsReceiptItemFormDialog
                    open={isItemDialogOpen}
                    onOpenChange={handleItemDialogOpenChange}
                    item={selectedItem}
                    onSave={handleSaveItem}
                    purchaseOrderId={watchedPurchaseOrderId}
                    goodsReceiptId={
                        activeGoodsReceipt?.id
                            ? String(activeGoodsReceipt.id)
                            : undefined
                    }
                />
            </EntityForm>
        );
    },
);
