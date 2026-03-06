'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { Controller, useFieldArray, useForm } from 'react-hook-form';

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
import { type GoodsReceipt, type GoodsReceiptFormData } from '@/types/goods-receipt';
import { goodsReceiptFormSchema } from '@/utils/schemas';

interface GoodsReceiptFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    goodsReceipt?: GoodsReceipt | null;
    item?: GoodsReceipt | null;
    entity?: GoodsReceipt | null;
    onSubmit: (data: GoodsReceiptFormData) => void;
    isLoading?: boolean;
}

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
            items: [
                {
                    purchase_order_item_id: '',
                    product_id: '',
                    unit_id: '',
                    quantity_received: 1,
                    quantity_accepted: 1,
                    quantity_rejected: 0,
                    unit_price: 0,
                    notes: '',
                },
            ],
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
                unit_id: it.unit?.id ? String(it.unit.id) : '',
                quantity_received: Number(it.quantity_received || 0),
                quantity_accepted: Number(it.quantity_accepted || 0),
                quantity_rejected: Number(it.quantity_rejected || 0),
                unit_price: Number(it.unit_price || 0),
                notes: it.notes || '',
            }))
            : [
                {
                    purchase_order_item_id: '',
                    product_id: '',
                    unit_id: '',
                    quantity_received: 1,
                    quantity_accepted: 1,
                    quantity_rejected: 0,
                    unit_price: 0,
                    notes: '',
                },
            ],
    };
};

export const GoodsReceiptForm = memo<GoodsReceiptFormProps>(function GoodsReceiptForm({
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

    const form = useForm<GoodsReceiptFormData>({
        resolver: zodResolver(goodsReceiptFormSchema as any),
        defaultValues,
    });

    const { fields, append, remove } = useFieldArray({
        control: form.control,
        name: 'items',
    });

    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    return (
        <EntityForm<GoodsReceiptFormData>
            form={form as any}
            open={open}
            onOpenChange={onOpenChange}
            title={activeGoodsReceipt ? 'Edit Goods Receipt' : 'Add New Goods Receipt'}
            onSubmit={onSubmit}
            isLoading={isLoading}
            className="sm:max-w-[1100px]"
        >
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <InputField name="gr_number" label="GR Number" placeholder="Auto-generated if empty" />

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
                            <div className="text-sm font-medium leading-none">Purchase Order</div>
                            <AsyncSelect
                                value={field.value ? String(field.value) : undefined}
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
                            <div className="text-sm font-medium leading-none">Warehouse</div>
                            <AsyncSelect
                                value={field.value ? String(field.value) : undefined}
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
                            <div className="text-sm font-medium leading-none">Received By</div>
                            <AsyncSelect
                                value={field.value ? String(field.value) : undefined}
                                onValueChange={field.onChange}
                                url="/api/employees"
                                placeholder="Select employee"
                                label="Received By"
                            />
                        </div>
                    )}
                />

                <InputField name="supplier_delivery_note" label="Supplier Delivery Note" placeholder="SJ-0001" />

                <div className="md:col-span-2">
                    <TextareaField name="notes" label="Notes" placeholder="Notes" rows={2} />
                </div>
            </div>

            <div className="space-y-3 border-t pt-4 mt-2">
                <div className="flex items-center justify-between">
                    <div className="text-sm font-semibold">Items</div>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() =>
                            append({
                                purchase_order_item_id: '',
                                product_id: '',
                                unit_id: '',
                                quantity_received: 1,
                                quantity_accepted: 1,
                                quantity_rejected: 0,
                                unit_price: 0,
                                notes: '',
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
                                <TableHead className="w-[140px]">PO Item ID</TableHead>
                                <TableHead className="w-[220px]">Product</TableHead>
                                <TableHead className="w-[140px]">Unit</TableHead>
                                <TableHead className="w-[120px]">Qty Received</TableHead>
                                <TableHead className="w-[120px]">Qty Accepted</TableHead>
                                <TableHead className="w-[120px]">Qty Rejected</TableHead>
                                <TableHead className="w-[140px]">Unit Price</TableHead>
                                <TableHead>Notes</TableHead>
                                <TableHead className="w-[80px] text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {fields.map((field, index) => (
                                <TableRow key={field.id}>
                                    <TableCell>
                                        <InputField name={`items.${index}.purchase_order_item_id`} label="" />
                                    </TableCell>
                                    <TableCell>
                                        <Controller
                                            control={form.control}
                                            name={`items.${index}.product_id`}
                                            render={({ field: itemField }) => (
                                                <AsyncSelect
                                                    value={itemField.value ? String(itemField.value) : undefined}
                                                    onValueChange={itemField.onChange}
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
                                            render={({ field: itemField }) => (
                                                <AsyncSelect
                                                    value={itemField.value ? String(itemField.value) : undefined}
                                                    onValueChange={itemField.onChange}
                                                    url="/api/units"
                                                    placeholder="Select unit"
                                                    label="Unit"
                                                />
                                            )}
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <InputField name={`items.${index}.quantity_received`} label="" type="number" />
                                    </TableCell>
                                    <TableCell>
                                        <InputField name={`items.${index}.quantity_accepted`} label="" type="number" />
                                    </TableCell>
                                    <TableCell>
                                        <InputField name={`items.${index}.quantity_rejected`} label="" type="number" />
                                    </TableCell>
                                    <TableCell>
                                        <InputField name={`items.${index}.unit_price`} label="" type="number" />
                                    </TableCell>
                                    <TableCell>
                                        <InputField name={`items.${index}.notes`} label="" />
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => remove(index)}
                                            disabled={fields.length === 1}
                                        >
                                            Remove
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </EntityForm>
    );
});
