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
import { type SupplierReturn, type SupplierReturnFormData } from '@/types/supplier-return';
import { supplierReturnFormSchema } from '@/utils/schemas';

interface SupplierReturnFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    supplierReturn?: SupplierReturn | null;
    item?: SupplierReturn | null;
    entity?: SupplierReturn | null;
    onSubmit: (data: SupplierReturnFormData) => void;
    isLoading?: boolean;
}

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
            return_date: new Date(),
            reason: 'defective',
            status: 'draft',
            notes: '',
            items: [
                {
                    goods_receipt_item_id: '',
                    product_id: '',
                    unit_id: '',
                    quantity_returned: 1,
                    unit_price: 0,
                    notes: '',
                },
            ],
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
            ? new Date(supplierReturn.return_date)
            : new Date(),
        reason: supplierReturn.reason,
        status: supplierReturn.status,
        notes: supplierReturn.notes || '',
        items: (supplierReturn.items || []).length
            ? (supplierReturn.items || []).map((it) => ({
                goods_receipt_item_id: String(it.goods_receipt_item_id),
                product_id: it.product?.id ? String(it.product.id) : '',
                unit_id: it.unit?.id ? String(it.unit.id) : '',
                quantity_returned: Number(it.quantity_returned || 0),
                unit_price: Number(it.unit_price || 0),
                notes: it.notes || '',
            }))
            : [
                {
                    goods_receipt_item_id: '',
                    product_id: '',
                    unit_id: '',
                    quantity_returned: 1,
                    unit_price: 0,
                    notes: '',
                },
            ],
    };
};

export const SupplierReturnForm = memo<SupplierReturnFormProps>(function SupplierReturnForm({
    open,
    onOpenChange,
    supplierReturn,
    item,
    entity,
    onSubmit,
    isLoading = false,
}) {
    const activeSupplierReturn = supplierReturn || item || entity;
    const defaultValues = useMemo(
        () => getSupplierReturnFormDefaults(activeSupplierReturn),
        [activeSupplierReturn],
    );

    const form = useForm<SupplierReturnFormData>({
        resolver: zodResolver(supplierReturnFormSchema as any),
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
        <EntityForm<SupplierReturnFormData>
            form={form as any}
            open={open}
            onOpenChange={onOpenChange}
            title={activeSupplierReturn ? 'Edit Supplier Return' : 'Add New Supplier Return'}
            onSubmit={onSubmit}
            isLoading={isLoading}
            className="sm:max-w-[1100px]"
        >
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <InputField name="return_number" label="Return Number" placeholder="Auto-generated if empty" />

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
                    name="goods_receipt_id"
                    render={({ field }) => (
                        <div className="space-y-2">
                            <div className="text-sm font-medium leading-none">Goods Receipt</div>
                            <AsyncSelect
                                value={field.value ? String(field.value) : undefined}
                                onValueChange={field.onChange}
                                url="/api/goods-receipts"
                                placeholder="Select goods receipt"
                                label="Goods Receipt"
                            />
                        </div>
                    )}
                />

                <Controller
                    control={form.control}
                    name="supplier_id"
                    render={({ field }) => (
                        <div className="space-y-2">
                            <div className="text-sm font-medium leading-none">Supplier</div>
                            <AsyncSelect
                                value={field.value ? String(field.value) : undefined}
                                onValueChange={field.onChange}
                                url="/api/suppliers"
                                placeholder="Select supplier"
                                label="Supplier"
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

                <DatePickerField name="return_date" label="Return Date" />

                <SelectField
                    name="reason"
                    label="Reason"
                    options={[
                        { value: 'defective', label: 'Defective' },
                        { value: 'wrong_item', label: 'Wrong Item' },
                        { value: 'excess_quantity', label: 'Excess Quantity' },
                        { value: 'damaged', label: 'Damaged' },
                        { value: 'other', label: 'Other' },
                    ]}
                    placeholder="Select reason"
                />

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
                                goods_receipt_item_id: '',
                                product_id: '',
                                unit_id: '',
                                quantity_returned: 1,
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
                                <TableHead className="w-[140px]">GR Item ID</TableHead>
                                <TableHead className="w-[220px]">Product</TableHead>
                                <TableHead className="w-[140px]">Unit</TableHead>
                                <TableHead className="w-[120px]">Qty Returned</TableHead>
                                <TableHead className="w-[140px]">Unit Price</TableHead>
                                <TableHead>Notes</TableHead>
                                <TableHead className="w-[80px] text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {fields.map((field, index) => (
                                <TableRow key={field.id}>
                                    <TableCell>
                                        <InputField name={`items.${index}.goods_receipt_item_id`} label="" />
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
                                        <InputField name={`items.${index}.quantity_returned`} label="" type="number" />
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
