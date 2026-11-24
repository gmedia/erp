'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo, useRef, useState } from 'react';
import { type Resolver, useFieldArray, useForm } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
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
import { Alert, AlertDescription } from '@/components/ui/alert';
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
        const isPoNotConfirmable =
            watchedStatus === 'confirmed' &&
            !!watchedPurchaseOrderId &&
            !!selectedPoStatus &&
            !CONFIRMABLE_PO_STATUSES.includes(selectedPoStatus);
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
                        ? 'Edit Goods Receipt'
                        : 'Add New Goods Receipt'
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
                className="sm:max-w-[1100px]"
            >
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <InputField
                        name="gr_number"
                        label="GR Number"
                        placeholder="Auto-generated if empty"
                    />

                    <SelectField
                        name="status"
                        label="Status"
                        options={[
                            { value: 'draft', label: 'Draft' },
                            { value: 'confirmed', label: 'Confirmed' },
                            { value: 'cancelled', label: 'Cancelled' },
                        ]}
                        placeholder="Select status"
                    />

                    <div className="space-y-2">
                        <AsyncSelectField<PurchaseOrderOption>
                            name="purchase_order_id"
                            label="Purchase Order"
                            url="/api/purchase-orders"
                            placeholder="Select purchase order"
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
                        label="Warehouse"
                        url="/api/warehouses"
                        placeholder="Select warehouse"
                    />

                    <DatePickerField name="receipt_date" label="Receipt Date" />

                    <AsyncSelectField
                        name="received_by"
                        label="Received By"
                        url="/api/employees"
                        placeholder="Select employee"
                    />

                    <InputField
                        name="supplier_delivery_note"
                        label="Supplier Delivery Note"
                        placeholder="SJ-0001"
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
                    <EntityFormItemSectionHeader
                        onAddItem={handleCreateNewItem}
                    />

                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[220px]">
                                        Product
                                    </TableHead>
                                    <TableHead className="w-[140px]">
                                        Unit
                                    </TableHead>
                                    <TableHead className="w-[120px]">
                                        Qty Received
                                    </TableHead>
                                    <TableHead className="w-[120px]">
                                        Qty Accepted
                                    </TableHead>
                                    <TableHead className="w-[120px]">
                                        Qty Rejected
                                    </TableHead>
                                    <TableHead className="w-[140px]">
                                        Unit Price
                                    </TableHead>
                                    <TableHead>Notes</TableHead>
                                    <TableHead className="w-[120px] text-right">
                                        Action
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
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
