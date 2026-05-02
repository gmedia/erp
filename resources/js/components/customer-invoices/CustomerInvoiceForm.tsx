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
    type CustomerInvoice,
    type CustomerInvoiceFormData,
} from '@/types/customer-invoice';
import { omitItemDisplayLabels } from '@/utils/entity-form-item';
import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';
import { customerInvoiceFormSchema } from '@/utils/schemas';
import { CustomerInvoiceItemFormDialog } from './CustomerInvoiceItemFormDialog';

interface CustomerInvoiceFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    customerInvoice?: CustomerInvoice | null;
    item?: CustomerInvoice | null;
    entity?: CustomerInvoice | null;
    onSubmit: (data: CustomerInvoiceFormData) => void;
    isLoading?: boolean;
}

const getCustomerInvoiceFormDefaults = (
    customerInvoice?: CustomerInvoice | null,
): CustomerInvoiceFormData => {
    if (!customerInvoice) {
        return {
            customer_id: '',
            branch_id: '',
            fiscal_year_id: '',
            invoice_date: new Date(),
            due_date: new Date(),
            payment_terms: '',
            currency: 'IDR',
            status: 'draft',
            notes: '',
            items: [],
        };
    }

    return {
        customer_id: customerInvoice.customer?.id
            ? String(customerInvoice.customer.id)
            : '',
        branch_id: customerInvoice.branch?.id
            ? String(customerInvoice.branch.id)
            : '',
        fiscal_year_id: customerInvoice.fiscal_year?.id
            ? String(customerInvoice.fiscal_year.id)
            : '',
        invoice_date: customerInvoice.invoice_date
            ? new Date(customerInvoice.invoice_date)
            : new Date(),
        due_date: customerInvoice.due_date
            ? new Date(customerInvoice.due_date)
            : new Date(),
        payment_terms: customerInvoice.payment_terms || '',
        currency: customerInvoice.currency || 'IDR',
        status: customerInvoice.status,
        notes: customerInvoice.notes || '',
        items: (customerInvoice.items || []).length
            ? (customerInvoice.items || []).map((it) => ({
                  product_id: it.product_id ? String(it.product_id) : '',
                  product_label: it.product_name || '',
                  account_id: it.account_id ? String(it.account_id) : '',
                  account_label: it.account_name || '',
                  unit_id: it.unit_id ? String(it.unit_id) : '',
                  unit_label: it.unit_name || '',
                  description: it.description || '',
                  quantity: Number(it.quantity || 0),
                  unit_price: Number(it.unit_price || 0),
                  discount_percent: Number(it.discount_percent || 0),
                  tax_percent: Number(it.tax_percent || 0),
                  notes: it.notes || '',
              }))
            : [],
    };
};

export const CustomerInvoiceForm = memo<CustomerInvoiceFormProps>(
    function CustomerInvoiceForm({
        open,
        onOpenChange,
        customerInvoice,
        item,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const activeCustomerInvoice = customerInvoice || item || entity;
        const defaultValues = useMemo(
            () => getCustomerInvoiceFormDefaults(activeCustomerInvoice),
            [activeCustomerInvoice],
        );

        const form = useForm<
            CustomerInvoiceFormData,
            unknown,
            CustomerInvoiceFormData
        >({
            resolver: zodResolver(customerInvoiceFormSchema) as Resolver<
                CustomerInvoiceFormData,
                unknown,
                CustomerInvoiceFormData
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

        useResetFormOnDefaultValues(form, defaultValues);

        const customerInvoiceStatusOptions = [
            { value: 'draft', label: 'Draft' },
            { value: 'sent', label: 'Sent' },
            { value: 'partially_paid', label: 'Partially Paid' },
            { value: 'paid', label: 'Paid' },
            { value: 'overdue', label: 'Overdue' },
            { value: 'cancelled', label: 'Cancelled' },
            { value: 'void', label: 'Void' },
        ];

        const currencyOptions = [
            { value: 'IDR', label: 'IDR' },
            { value: 'USD', label: 'USD' },
            { value: 'EUR', label: 'EUR' },
        ];

        const handleSubmit = (data: CustomerInvoiceFormData) => {
            onSubmit({
                ...data,
                items: data.items.map(omitItemDisplayLabels),
            });
        };

        return (
            <>
                <EntityForm
                    form={form}
                    open={open}
                    onOpenChange={onOpenChange}
                    title={
                        activeCustomerInvoice
                            ? 'Edit Customer Invoice'
                            : 'Add Customer Invoice'
                    }
                    onSubmit={handleSubmit}
                    isLoading={isLoading}
                >
                    <div className="grid grid-cols-2 gap-4">
                        <AsyncSelectField
                            name="customer_id"
                            label="Customer"
                            placeholder="Select Customer"
                            url="/api/customers"
                        />
                        <AsyncSelectField
                            name="branch_id"
                            label="Branch"
                            placeholder="Select Branch"
                            url="/api/branches"
                        />
                        <AsyncSelectField
                            name="fiscal_year_id"
                            label="Fiscal Year"
                            placeholder="Select Fiscal Year"
                            url="/api/fiscal-years"
                        />
                        <DatePickerField
                            name="invoice_date"
                            label="Invoice Date"
                        />
                        <DatePickerField name="due_date" label="Due Date" />
                        <InputField
                            name="payment_terms"
                            label="Payment Terms"
                            placeholder="e.g., Net 30"
                        />
                        <SelectField
                            name="currency"
                            label="Currency"
                            options={currencyOptions}
                            placeholder="Select Currency"
                        />
                        <SelectField
                            name="status"
                            label="Status"
                            options={customerInvoiceStatusOptions}
                            placeholder="Select Status"
                        />
                    </div>
                    <TextareaField
                        name="notes"
                        label="Notes"
                        placeholder="Additional notes..."
                    />

                    <EntityFormItemSectionHeader
                        title="Invoice Items"
                        onAddItem={handleCreateNewItem}
                    />

                    {fields.length === 0 ? (
                        <EntityFormItemEmptyRow
                            colSpan={8}
                            message="No items added yet. Click 'Add Item' to start."
                        />
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12">#</TableHead>
                                    <TableHead>Product/Account</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead className="text-right">
                                        Qty
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Unit Price
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Discount %
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Tax %
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Line Total
                                    </TableHead>
                                    <TableHead className="w-20">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {fields.map((field, index) => {
                                    const lineTotal =
                                        field.quantity *
                                        field.unit_price *
                                        (1 -
                                            (field.discount_percent || 0) /
                                                100) *
                                        (1 + (field.tax_percent || 0) / 100);
                                    return (
                                        <TableRow key={field.id}>
                                            <TableCell className="font-mono text-xs text-muted-foreground">
                                                {String(index + 1)}
                                            </TableCell>
                                            <TableCell>
                                                <div className="space-y-1">
                                                    <div className="font-medium">
                                                        {field.product_label ||
                                                            'No Product'}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {field.account_label ||
                                                            'No Account'}
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {field.description || '-'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {formatNumberByRegionalSettings(
                                                    field.quantity,
                                                    {
                                                        locale: 'id-ID',
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2,
                                                    },
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {formatCurrencyByRegionalSettings(
                                                    field.unit_price,
                                                    {
                                                        locale: 'id-ID',
                                                        currency:
                                                            selectedCurrency ||
                                                            'IDR',
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2,
                                                    },
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {formatNumberByRegionalSettings(
                                                    field.discount_percent || 0,
                                                    {
                                                        locale: 'id-ID',
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2,
                                                    },
                                                )}
                                                %
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {formatNumberByRegionalSettings(
                                                    field.tax_percent || 0,
                                                    {
                                                        locale: 'id-ID',
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2,
                                                    },
                                                )}
                                                %
                                            </TableCell>
                                            <TableCell className="text-right font-medium">
                                                {formatCurrencyByRegionalSettings(
                                                    lineTotal,
                                                    {
                                                        locale: 'id-ID',
                                                        currency:
                                                            selectedCurrency ||
                                                            'IDR',
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2,
                                                    },
                                                )}
                                            </TableCell>
                                            <EntityFormItemActionsCell
                                                index={index}
                                                onEdit={() =>
                                                    handleEditItem(index)
                                                }
                                                onRemove={() => remove(index)}
                                            />
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    )}
                </EntityForm>

                <CustomerInvoiceItemFormDialog
                    open={isItemDialogOpen}
                    onOpenChange={handleItemDialogOpenChange}
                    item={selectedItem}
                    onSave={handleSaveItem}
                />
            </>
        );
    },
);
