'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useMemo } from 'react';
import {
    type Resolver,
    Controller,
    useFieldArray,
    useForm,
} from 'react-hook-form';

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
import { useEntityFormItemDialog } from '@/hooks/useEntityFormItemDialog';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import {
    type SupplierReturn,
    type SupplierReturnFormData,
} from '@/types/supplier-return';
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

const createEmptySupplierReturnItem =
    (): SupplierReturnFormData['items'][number] => ({
        goods_receipt_item_id: '',
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
            return_date: new Date(),
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
            ? new Date(supplierReturn.return_date)
            : new Date(),
        reason: supplierReturn.reason,
        status: supplierReturn.status,
        notes: supplierReturn.notes || '',
        items: (supplierReturn.items || []).length
            ? (supplierReturn.items || []).map((it) => ({
                  goods_receipt_item_id: String(it.goods_receipt_item_id),
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
                        ? 'Edit Supplier Return'
                        : 'Add New Supplier Return'
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
                className="sm:max-w-[1100px]"
            >
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <InputField
                        name="return_number"
                        label="Return Number"
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
                        name="goods_receipt_id"
                        render={({ field }) => (
                            <div className="space-y-2">
                                <div className="text-sm leading-none font-medium">
                                    Goods Receipt
                                </div>
                                <AsyncSelect
                                    value={
                                        field.value
                                            ? String(field.value)
                                            : undefined
                                    }
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
                                <div className="text-sm leading-none font-medium">
                                    Supplier
                                </div>
                                <AsyncSelect
                                    value={
                                        field.value
                                            ? String(field.value)
                                            : undefined
                                    }
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

                    <DatePickerField name="return_date" label="Return Date" />

                    <SelectField
                        name="reason"
                        label="Reason"
                        options={[
                            { value: 'defective', label: 'Defective' },
                            { value: 'wrong_item', label: 'Wrong Item' },
                            {
                                value: 'excess_quantity',
                                label: 'Excess Quantity',
                            },
                            { value: 'damaged', label: 'Damaged' },
                            { value: 'other', label: 'Other' },
                        ]}
                        placeholder="Select reason"
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
                                    <TableHead className="w-[140px]">
                                        GR Item ID
                                    </TableHead>
                                    <TableHead className="w-[220px]">
                                        Product
                                    </TableHead>
                                    <TableHead className="w-[140px]">
                                        Unit
                                    </TableHead>
                                    <TableHead className="w-[120px]">
                                        Qty Returned
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
                                    const supplierReturnItem =
                                        watchedItems?.[index] ??
                                        createEmptySupplierReturnItem();

                                    return (
                                        <TableRow key={field.id}>
                                            <TableCell>
                                                {formatItemReference(
                                                    undefined,
                                                    supplierReturnItem.goods_receipt_item_id,
                                                )}
                                            </TableCell>
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
                                    <EntityFormItemEmptyRow colSpan={7} />
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
                />
            </EntityForm>
        );
    },
);
