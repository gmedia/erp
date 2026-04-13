'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useMemo } from 'react';
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
    type PurchaseOrder,
    type PurchaseOrderFormData,
} from '@/types/purchase-order';
import {
    formatItemReference,
    omitItemDisplayLabels,
} from '@/utils/entity-form-item';
import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';
import { purchaseOrderFormSchema } from '@/utils/schemas';
import { PurchaseOrderItemFormDialog } from './PurchaseOrderItemFormDialog';

interface PurchaseOrderFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    purchaseOrder?: PurchaseOrder | null;
    item?: PurchaseOrder | null;
    entity?: PurchaseOrder | null;
    onSubmit: (data: PurchaseOrderFormData) => void;
    isLoading?: boolean;
}

const createEmptyPurchaseOrderItem =
    (): PurchaseOrderFormData['items'][number] => ({
        purchase_request_item_id: '',
        product_id: '',
        product_label: '',
        unit_id: '',
        unit_label: '',
        quantity: 1,
        unit_price: 0,
        discount_percent: 0,
        tax_percent: 0,
        notes: '',
    });

const getPurchaseOrderFormDefaults = (
    purchaseOrder?: PurchaseOrder | null,
): PurchaseOrderFormData => {
    if (!purchaseOrder) {
        return {
            po_number: '',
            supplier_id: '',
            warehouse_id: '',
            order_date: new Date(),
            expected_delivery_date: null,
            payment_terms: '',
            currency: 'IDR',
            status: 'draft',
            notes: '',
            shipping_address: '',
            items: [],
        };
    }

    return {
        po_number: purchaseOrder.po_number || '',
        supplier_id: purchaseOrder.supplier?.id
            ? String(purchaseOrder.supplier.id)
            : '',
        warehouse_id: purchaseOrder.warehouse?.id
            ? String(purchaseOrder.warehouse.id)
            : '',
        order_date: purchaseOrder.order_date
            ? new Date(purchaseOrder.order_date)
            : new Date(),
        expected_delivery_date: purchaseOrder.expected_delivery_date
            ? new Date(purchaseOrder.expected_delivery_date)
            : null,
        payment_terms: purchaseOrder.payment_terms || '',
        currency: purchaseOrder.currency || 'IDR',
        status: purchaseOrder.status,
        notes: purchaseOrder.notes || '',
        shipping_address: purchaseOrder.shipping_address || '',
        items: (purchaseOrder.items || []).length
            ? (purchaseOrder.items || []).map((it) => ({
                  purchase_request_item_id: it.purchase_request_item_id
                      ? String(it.purchase_request_item_id)
                      : '',
                  product_id: it.product?.id ? String(it.product.id) : '',
                  product_label: it.product?.name || '',
                  unit_id: it.unit?.id ? String(it.unit.id) : '',
                  unit_label: it.unit?.name || '',
                  quantity: Number(it.quantity || 0),
                  unit_price: Number(it.unit_price || 0),
                  discount_percent: Number(it.discount_percent || 0),
                  tax_percent: Number(it.tax_percent || 0),
                  notes: it.notes || '',
              }))
            : [],
    };
};

export const PurchaseOrderForm = memo<PurchaseOrderFormProps>(
    function PurchaseOrderForm({
        open,
        onOpenChange,
        purchaseOrder,
        item,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const activePurchaseOrder = purchaseOrder || item || entity;
        const defaultValues = useMemo(
            () => getPurchaseOrderFormDefaults(activePurchaseOrder),
            [activePurchaseOrder],
        );

        const form = useForm<
            PurchaseOrderFormData,
            unknown,
            PurchaseOrderFormData
        >({
            resolver: zodResolver(purchaseOrderFormSchema) as Resolver<
                PurchaseOrderFormData,
                unknown,
                PurchaseOrderFormData
            >,
            defaultValues,
        });

        const { fields, append, remove, update } = useFieldArray({
            control: form.control,
            name: 'items',
        });
        const watchedItems = form.watch('items');
        const selectedCurrency = form.watch('currency');
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

        const handleSubmit = (data: PurchaseOrderFormData) => {
            onSubmit({
                ...data,
                items: data.items.map(omitItemDisplayLabels),
            });
        };

        useResetFormOnDefaultValues(form, defaultValues);

        return (
            <EntityForm<PurchaseOrderFormData>
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    activePurchaseOrder
                        ? 'Edit Purchase Order'
                        : 'Add New Purchase Order'
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
                className="sm:max-w-[1100px]"
            >
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <InputField
                        name="po_number"
                        label="PO Number"
                        placeholder="Auto-generated if empty"
                    />

                    <SelectField
                        name="status"
                        label="Status"
                        options={[
                            { value: 'draft', label: 'Draft' },
                            {
                                value: 'pending_approval',
                                label: 'Pending Approval',
                            },
                            { value: 'confirmed', label: 'Confirmed' },
                            { value: 'rejected', label: 'Rejected' },
                            {
                                value: 'partially_received',
                                label: 'Partially Received',
                            },
                            {
                                value: 'fully_received',
                                label: 'Fully Received',
                            },
                            { value: 'cancelled', label: 'Cancelled' },
                            { value: 'closed', label: 'Closed' },
                        ]}
                        placeholder="Select status"
                    />

                    <AsyncSelectField
                        name="supplier_id"
                        label="Supplier"
                        url="/api/suppliers"
                        placeholder="Select supplier"
                    />

                    <AsyncSelectField
                        name="warehouse_id"
                        label="Warehouse"
                        url="/api/warehouses"
                        placeholder="Select warehouse"
                    />

                    <DatePickerField name="order_date" label="Order Date" />
                    <DatePickerField
                        name="expected_delivery_date"
                        label="Expected Delivery Date"
                    />

                    <InputField
                        name="payment_terms"
                        label="Payment Terms"
                        placeholder="Net 30"
                    />
                    <InputField
                        name="currency"
                        label="Currency"
                        placeholder="IDR"
                    />

                    <div className="md:col-span-2">
                        <TextareaField
                            name="shipping_address"
                            label="Shipping Address"
                            placeholder="Shipping address"
                            rows={2}
                        />
                    </div>

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
                                        Qty
                                    </TableHead>
                                    <TableHead className="w-[140px]">
                                        Unit Price
                                    </TableHead>
                                    <TableHead className="w-[130px]">
                                        Disc %
                                    </TableHead>
                                    <TableHead className="w-[130px]">
                                        Tax %
                                    </TableHead>
                                    <TableHead>Notes</TableHead>
                                    <TableHead className="w-[120px] text-right">
                                        Action
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {fields.map((field, index) => {
                                    const purchaseOrderItem =
                                        watchedItems?.[index] ??
                                        createEmptyPurchaseOrderItem();

                                    return (
                                        <TableRow key={field.id}>
                                            <TableCell>
                                                {formatItemReference(
                                                    purchaseOrderItem.product_label,
                                                    purchaseOrderItem.product_id,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {formatItemReference(
                                                    purchaseOrderItem.unit_label,
                                                    purchaseOrderItem.unit_id,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {formatNumberByRegionalSettings(
                                                    purchaseOrderItem.quantity ??
                                                        0,
                                                    {
                                                        locale: 'id-ID',
                                                        minimumFractionDigits: 0,
                                                        maximumFractionDigits: 2,
                                                    },
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {formatCurrencyByRegionalSettings(
                                                    purchaseOrderItem.unit_price ??
                                                        0,
                                                    {
                                                        locale: 'id-ID',
                                                        currency:
                                                            selectedCurrency ||
                                                            undefined,
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2,
                                                    },
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {formatNumberByRegionalSettings(
                                                    purchaseOrderItem.discount_percent ??
                                                        0,
                                                    {
                                                        locale: 'id-ID',
                                                        minimumFractionDigits: 0,
                                                        maximumFractionDigits: 2,
                                                    },
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {formatNumberByRegionalSettings(
                                                    purchaseOrderItem.tax_percent ??
                                                        0,
                                                    {
                                                        locale: 'id-ID',
                                                        minimumFractionDigits: 0,
                                                        maximumFractionDigits: 2,
                                                    },
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {purchaseOrderItem.notes || '-'}
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

                <PurchaseOrderItemFormDialog
                    open={isItemDialogOpen}
                    onOpenChange={handleItemDialogOpenChange}
                    item={selectedItem}
                    onSave={handleSaveItem}
                />
            </EntityForm>
        );
    },
);
