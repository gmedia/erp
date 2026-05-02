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
    type SupplierBill,
    type SupplierBillFormData,
} from '@/types/supplier-bill';
import {
    formatItemReference,
    omitItemDisplayLabels,
} from '@/utils/entity-form-item';
import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';
import { supplierBillFormSchema } from '@/utils/schemas';
import { SupplierBillItemFormDialog } from './SupplierBillItemFormDialog';

interface SupplierBillFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: SupplierBill | null;
    onSubmit: (data: SupplierBillFormData) => void;
    isLoading?: boolean;
}

const createEmptySupplierBillItem =
    (): SupplierBillFormData['items'][number] => ({
        product_id: '',
        product_label: '',
        account_id: '',
        account_label: '',
        description: '',
        quantity: 1,
        unit_price: 0,
        discount_percent: 0,
        tax_percent: 0,
        goods_receipt_item_id: '',
        notes: '',
    });

const getSupplierBillFormDefaults = (
    supplierBill?: SupplierBill | null,
): SupplierBillFormData => {
    if (!supplierBill) {
        return {
            bill_number: '',
            supplier_id: '',
            branch_id: '',
            fiscal_year_id: '',
            purchase_order_id: '',
            goods_receipt_id: '',
            supplier_invoice_number: '',
            supplier_invoice_date: null,
            bill_date: new Date(),
            due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000), // 30 days from now
            payment_terms: '',
            currency: 'IDR',
            status: 'draft',
            notes: '',
            items: [],
        };
    }

    return {
        bill_number: supplierBill.bill_number || '',
        supplier_id: supplierBill.supplier?.id
            ? String(supplierBill.supplier.id)
            : '',
        branch_id: supplierBill.branch?.id
            ? String(supplierBill.branch.id)
            : '',
        fiscal_year_id: supplierBill.fiscal_year?.id
            ? String(supplierBill.fiscal_year.id)
            : '',
        purchase_order_id: supplierBill.purchase_order?.id
            ? String(supplierBill.purchase_order.id)
            : '',
        goods_receipt_id: supplierBill.goods_receipt?.id
            ? String(supplierBill.goods_receipt.id)
            : '',
        supplier_invoice_number: supplierBill.supplier_invoice_number || '',
        supplier_invoice_date: supplierBill.supplier_invoice_date
            ? new Date(supplierBill.supplier_invoice_date)
            : null,
        bill_date: supplierBill.bill_date
            ? new Date(supplierBill.bill_date)
            : new Date(),
        due_date: supplierBill.due_date
            ? new Date(supplierBill.due_date)
            : new Date(Date.now() + 30 * 24 * 60 * 60 * 1000),
        payment_terms: supplierBill.payment_terms || '',
        currency: supplierBill.currency || 'IDR',
        status: supplierBill.status,
        notes: supplierBill.notes || '',
        items: (supplierBill.items || []).length
            ? (supplierBill.items || []).map((it) => ({
                  product_id: it.product_id ? String(it.product_id) : '',
                  product_label: it.product_name || '',
                  account_id: it.account_id ? String(it.account_id) : '',
                  account_label: it.account_name || '',
                  description: it.description || '',
                  quantity: Number(it.quantity || 0),
                  unit_price: Number(it.unit_price || 0),
                  discount_percent: Number(it.discount_percent || 0),
                  tax_percent: Number(it.tax_percent || 0),
                  goods_receipt_item_id: it.goods_receipt_item_id
                      ? String(it.goods_receipt_item_id)
                      : '',
                  notes: it.notes || '',
              }))
            : [],
    };
};

export const SupplierBillForm = memo<SupplierBillFormProps>(
    function SupplierBillForm({
        open,
        onOpenChange,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const defaultValues = useMemo(
            () => getSupplierBillFormDefaults(entity),
            [entity],
        );

        const form = useForm<
            SupplierBillFormData,
            unknown,
            SupplierBillFormData
        >({
            resolver: zodResolver(supplierBillFormSchema) as Resolver<
                SupplierBillFormData,
                unknown,
                SupplierBillFormData
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

        const handleSubmit = (data: SupplierBillFormData) => {
            onSubmit({
                ...data,
                items: data.items.map(omitItemDisplayLabels),
            });
        };

        useResetFormOnDefaultValues(form, defaultValues);

        return (
            <EntityForm<SupplierBillFormData>
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    entity ? 'Edit Supplier Bill' : 'Add New Supplier Bill'
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
                className="sm:max-w-[1100px]"
            >
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <InputField
                        name="bill_number"
                        label="Bill Number"
                        placeholder="Auto-generated if empty"
                    />

                    <SelectField
                        name="status"
                        label="Status"
                        options={[
                            { value: 'draft', label: 'Draft' },
                            { value: 'confirmed', label: 'Confirmed' },
                            { value: 'partially_paid', label: 'Partially Paid' },
                            { value: 'paid', label: 'Paid' },
                            { value: 'overdue', label: 'Overdue' },
                            { value: 'cancelled', label: 'Cancelled' },
                            { value: 'void', label: 'Void' },
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
                        name="branch_id"
                        label="Branch"
                        url="/api/branches"
                        placeholder="Select branch"
                    />

                    <AsyncSelectField
                        name="fiscal_year_id"
                        label="Fiscal Year"
                        url="/api/fiscal-years"
                        placeholder="Select fiscal year"
                    />

                    <AsyncSelectField
                        name="purchase_order_id"
                        label="Purchase Order"
                        url="/api/purchase-orders"
                        placeholder="Select purchase order"
                    />

                    <AsyncSelectField
                        name="goods_receipt_id"
                        label="Goods Receipt"
                        url="/api/goods-receipts"
                        placeholder="Select goods receipt"
                    />

                    <InputField
                        name="supplier_invoice_number"
                        label="Supplier Invoice Number"
                        placeholder="Supplier invoice number"
                    />

                    <DatePickerField
                        name="supplier_invoice_date"
                        label="Supplier Invoice Date"
                    />

                    <DatePickerField name="bill_date" label="Bill Date" />
                    <DatePickerField name="due_date" label="Due Date" />

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
                                        Description
                                    </TableHead>
                                    <TableHead className="w-[140px]">
                                        Account
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
                                    const supplierBillItem =
                                        watchedItems?.[index] ??
                                        createEmptySupplierBillItem();

                                    return (
                                        <TableRow key={field.id}>
                                            <TableCell>
                                                {formatItemReference(
                                                    supplierBillItem.description,
                                                    supplierBillItem.product_id,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {formatItemReference(
                                                    supplierBillItem.account_label,
                                                    supplierBillItem.account_id,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {formatNumberByRegionalSettings(
                                                    supplierBillItem.quantity ??
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
                                                    supplierBillItem.unit_price ??
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
                                                    supplierBillItem.discount_percent ??
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
                                                    supplierBillItem.tax_percent ??
                                                        0,
                                                    {
                                                        locale: 'id-ID',
                                                        minimumFractionDigits: 0,
                                                        maximumFractionDigits: 2,
                                                    },
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {supplierBillItem.notes || '-'}
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

                <SupplierBillItemFormDialog
                    open={isItemDialogOpen}
                    onOpenChange={handleItemDialogOpenChange}
                    item={selectedItem}
                    onSave={handleSaveItem}
                />
            </EntityForm>
        );
    },
);