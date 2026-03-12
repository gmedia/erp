'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { memo, useEffect, useMemo, useState } from 'react';
import {
    type Resolver,
    Controller,
    useFieldArray,
    useForm,
} from 'react-hook-form';

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
import {
    type GoodsReceipt,
    type GoodsReceiptFormData,
} from '@/types/goods-receipt';
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

const createEmptyGoodsReceiptItem =
    (): GoodsReceiptFormData['items'][number] => ({
        purchase_order_item_id: '',
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

const getGoodsReceiptFormDefaults = (
    goodsReceipt?: GoodsReceipt | null,
): GoodsReceiptFormData => {
    if (!goodsReceipt) {
        return {
            gr_number: '',
            purchase_order_id: '',
            warehouse_id: '',
            receipt_date: new Date(),
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
            ? new Date(goodsReceipt.receipt_date)
            : new Date(),
        supplier_delivery_note: goodsReceipt.supplier_delivery_note || '',
        status: goodsReceipt.status,
        received_by: goodsReceipt.received_by?.id
            ? String(goodsReceipt.received_by.id)
            : '',
        notes: goodsReceipt.notes || '',
        items: (goodsReceipt.items || []).length
            ? (goodsReceipt.items || []).map((it) => ({
                  purchase_order_item_id: String(it.purchase_order_item_id),
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

        const handleSubmit = (data: GoodsReceiptFormData) => {
            onSubmit({
                ...data,
                items: data.items.map(omitDisplayLabels),
            });
        };

        useEffect(() => {
            form.reset(defaultValues);
        }, [form, defaultValues]);

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

                    <Controller
                        control={form.control}
                        name="purchase_order_id"
                        render={({ field }) => (
                            <div className="space-y-2">
                                <div className="text-sm leading-none font-medium">
                                    Purchase Order
                                </div>
                                <AsyncSelect
                                    value={
                                        field.value
                                            ? String(field.value)
                                            : undefined
                                    }
                                    onValueChange={field.onChange}
                                    url="/api/purchase-orders"
                                    placeholder="Select purchase order"
                                    label="Purchase Order"
                                />
                            </div>
                        )}
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

                    <DatePickerField name="receipt_date" label="Receipt Date" />

                    <Controller
                        control={form.control}
                        name="received_by"
                        render={({ field }) => (
                            <div className="space-y-2">
                                <div className="text-sm leading-none font-medium">
                                    Received By
                                </div>
                                <AsyncSelect
                                    value={
                                        field.value
                                            ? String(field.value)
                                            : undefined
                                    }
                                    onValueChange={field.onChange}
                                    url="/api/employees"
                                    placeholder="Select employee"
                                    label="Received By"
                                />
                            </div>
                        )}
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
                                    <TableHead className="w-[140px]">
                                        PO Item ID
                                    </TableHead>
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
                                        watchedItems?.[index] ||
                                        createEmptyGoodsReceiptItem();

                                    return (
                                        <TableRow key={field.id}>
                                            <TableCell>
                                                {formatItemReference(
                                                    undefined,
                                                    goodsReceiptItem.purchase_order_item_id,
                                                )}
                                            </TableCell>
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
                                            <TableCell className="text-right">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        handleEditItem(index)
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
                                })}
                                {!fields.length && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={9}
                                            className="py-6 text-center text-muted-foreground"
                                        >
                                            No items added yet.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </div>

                <GoodsReceiptItemFormDialog
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
