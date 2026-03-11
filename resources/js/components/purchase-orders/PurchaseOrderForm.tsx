'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo, useState } from 'react';
import { type Resolver, Controller, useFieldArray, useForm } from 'react-hook-form';
import { Pencil, Plus, Trash2 } from 'lucide-react';

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
import { type PurchaseOrder, type PurchaseOrderFormData } from '@/types/purchase-order';
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

const createEmptyPurchaseOrderItem = (): PurchaseOrderFormData['items'][number] => ({
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

const formatItemReference = (label?: string, id?: string) => {
    if (label) {
        return label;
    }

    if (id) {
        return `#${id}`;
    }

    return '-';
};

const omitDisplayLabels = <T extends { product_label?: string; unit_label?: string }>(
    item: T,
) => {
    const nextItem = { ...item };
    delete nextItem.product_label;
    delete nextItem.unit_label;

    return nextItem;
};

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
        supplier_id: purchaseOrder.supplier?.id ? String(purchaseOrder.supplier.id) : '',
        warehouse_id: purchaseOrder.warehouse?.id ? String(purchaseOrder.warehouse.id) : '',
        order_date: purchaseOrder.order_date ? new Date(purchaseOrder.order_date) : new Date(),
        expected_delivery_date: purchaseOrder.expected_delivery_date ? new Date(purchaseOrder.expected_delivery_date) : null,
        payment_terms: purchaseOrder.payment_terms || '',
        currency: purchaseOrder.currency || 'IDR',
        status: purchaseOrder.status,
        notes: purchaseOrder.notes || '',
        shipping_address: purchaseOrder.shipping_address || '',
        items: (purchaseOrder.items || []).length
            ? (purchaseOrder.items || []).map((it) => ({
                purchase_request_item_id: it.purchase_request_item_id ? String(it.purchase_request_item_id) : '',
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

export const PurchaseOrderForm = memo<PurchaseOrderFormProps>(function PurchaseOrderForm({
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

    const form = useForm<PurchaseOrderFormData, unknown, PurchaseOrderFormData>({
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

    const handleSubmit = (data: PurchaseOrderFormData) => {
        onSubmit({
            ...data,
            items: data.items.map(omitDisplayLabels),
        });
    };

    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    return (
        <EntityForm<PurchaseOrderFormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={activePurchaseOrder ? 'Edit Purchase Order' : 'Add New Purchase Order'}
            onSubmit={handleSubmit}
            isLoading={isLoading}
            className="sm:max-w-[1100px]"
        >
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <InputField name="po_number" label="PO Number" placeholder="Auto-generated if empty" />

                <SelectField
                    name="status"
                    label="Status"
                    options={[
                        { value: 'draft', label: 'Draft' },
                        { value: 'pending_approval', label: 'Pending Approval' },
                        { value: 'confirmed', label: 'Confirmed' },
                        { value: 'rejected', label: 'Rejected' },
                        { value: 'partially_received', label: 'Partially Received' },
                        { value: 'fully_received', label: 'Fully Received' },
                        { value: 'cancelled', label: 'Cancelled' },
                        { value: 'closed', label: 'Closed' },
                    ]}
                    placeholder="Select status"
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

                <DatePickerField name="order_date" label="Order Date" />
                <DatePickerField name="expected_delivery_date" label="Expected Delivery Date" />

                <InputField name="payment_terms" label="Payment Terms" placeholder="Net 30" />
                <InputField name="currency" label="Currency" placeholder="IDR" />

                <div className="md:col-span-2">
                    <TextareaField name="shipping_address" label="Shipping Address" placeholder="Shipping address" rows={2} />
                </div>

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
                                <TableHead className="w-[220px]">Product</TableHead>
                                <TableHead className="w-[140px]">Unit</TableHead>
                                <TableHead className="w-[120px]">Qty</TableHead>
                                <TableHead className="w-[140px]">Unit Price</TableHead>
                                <TableHead className="w-[130px]">Disc %</TableHead>
                                <TableHead className="w-[130px]">Tax %</TableHead>
                                <TableHead>Notes</TableHead>
                                <TableHead className="w-[120px] text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {fields.map((field, index) => {
                                const purchaseOrderItem = watchedItems?.[index] || createEmptyPurchaseOrderItem();

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
                                        <TableCell>{purchaseOrderItem.quantity ?? 0}</TableCell>
                                        <TableCell>{purchaseOrderItem.unit_price ?? 0}</TableCell>
                                        <TableCell>{purchaseOrderItem.discount_percent ?? 0}</TableCell>
                                        <TableCell>{purchaseOrderItem.tax_percent ?? 0}</TableCell>
                                        <TableCell>{purchaseOrderItem.notes || '-'}</TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => handleEditItem(index)}
                                                title="Edit item"
                                                aria-label={`Edit item ${index + 1}`}
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => remove(index)}
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
                                    <TableCell colSpan={8} className="py-6 text-center text-muted-foreground">
                                        No items added yet.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <PurchaseOrderItemFormDialog
                open={isItemDialogOpen}
                onOpenChange={(nextOpen) => {
                    setIsItemDialogOpen(nextOpen);
                    if (!nextOpen) {
                        setEditingIndex(null);
                    }
                }}
                item={editingIndex !== null ? watchedItems?.[editingIndex] || null : null}
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
});
