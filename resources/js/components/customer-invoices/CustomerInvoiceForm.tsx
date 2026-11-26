'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useMemo } from 'react';
import { type Resolver, useFieldArray, useForm } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import { DocumentDimensionFields } from '@/components/common/DocumentDimensionFields';
import EntityForm from '@/components/common/EntityForm';
import { EntityFormItemSectionHeader } from '@/components/common/EntityFormItemTable';
import { InputField } from '@/components/common/InputField';
import { PostedDocumentLockNotice } from '@/components/common/PostedDocumentLockNotice';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { TransactionLineItemsTable } from '@/components/common/TransactionLineItemsTable';
import { useTranslation } from '@/contexts/i18n-context';
import { useEntityFormItemDialog } from '@/hooks/useEntityFormItemDialog';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import {
    type CustomerInvoice,
    type CustomerInvoiceFormData,
} from '@/types/customer-invoice';
import { toDateInputValue } from '@/utils/date-format';
import { omitItemDisplayLabels } from '@/utils/entity-form-item';
import { getRegionalNumberFormatSettings } from '@/utils/number-format';
import { isPostedDocumentLocked } from '@/utils/posted-document-lock';
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
            department_id: '',
            fiscal_year_id: '',
            invoice_date: toDateInputValue(new Date()),
            due_date: toDateInputValue(new Date()),
            payment_terms: '',
            currency: getRegionalNumberFormatSettings().currency,
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
        department_id: customerInvoice.department?.id
            ? String(customerInvoice.department.id)
            : '',
        fiscal_year_id: customerInvoice.fiscal_year?.id
            ? String(customerInvoice.fiscal_year.id)
            : '',
        invoice_date: customerInvoice.invoice_date
            ? toDateInputValue(customerInvoice.invoice_date)
            : toDateInputValue(new Date()),
        due_date: customerInvoice.due_date
            ? toDateInputValue(customerInvoice.due_date)
            : toDateInputValue(new Date()),
        payment_terms: customerInvoice.payment_terms || '',
        currency:
            customerInvoice.currency ||
            getRegionalNumberFormatSettings().currency,
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
        const { t } = useTranslation();
        const fl = (key: string, fallback: string) => {
            const translated = t(`form_fields.customer_invoices.${key}`);
            return translated === `form_fields.customer_invoices.${key}`
                ? fallback
                : translated;
        };
        const activeCustomerInvoice = customerInvoice || item || entity;
        const isLocked = isPostedDocumentLocked(activeCustomerInvoice);
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
            { value: 'draft', label: fl('status_draft', 'Draft') },
            { value: 'sent', label: fl('status_sent', 'Sent') },
            {
                value: 'partially_paid',
                label: fl('status_partially_paid', 'Partially Paid'),
            },
            { value: 'paid', label: fl('status_paid', 'Paid') },
            { value: 'overdue', label: fl('status_overdue', 'Overdue') },
            {
                value: 'cancelled',
                label: fl('status_cancelled', 'Cancelled'),
            },
            { value: 'void', label: fl('status_void', 'Void') },
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
                            ? fl('edit_title', 'Edit Customer Invoice')
                            : fl('add_title', 'Add Customer Invoice')
                    }
                    onSubmit={handleSubmit}
                    isLoading={isLoading}
                    className="sm:max-w-[1100px]"
                >
                    <PostedDocumentLockNotice locked={isLocked} />
                    <div className="grid grid-cols-2 gap-4">
                        <AsyncSelectField
                            name="customer_id"
                            label={fl('customer', 'Customer')}
                            placeholder={fl(
                                'customer_placeholder',
                                'Select Customer',
                            )}
                            url="/api/customers"
                        />
                        <DocumentDimensionFields fl={fl} />
                        <DatePickerField
                            name="invoice_date"
                            label={fl('invoice_date', 'Invoice Date')}
                        />
                        <DatePickerField
                            name="due_date"
                            label={fl('due_date', 'Due Date')}
                        />
                        <InputField
                            name="payment_terms"
                            label={fl('payment_terms', 'Payment Terms')}
                            placeholder={fl(
                                'payment_terms_placeholder',
                                'e.g., Net 30',
                            )}
                        />
                        <SelectField
                            name="status"
                            label={fl('status', 'Status')}
                            options={customerInvoiceStatusOptions}
                            placeholder={fl(
                                'status_placeholder',
                                'Select Status',
                            )}
                        />
                    </div>
                    <TextareaField
                        name="notes"
                        label={fl('notes', 'Notes')}
                        placeholder={fl(
                            'notes_placeholder',
                            'Additional notes...',
                        )}
                    />

                    <EntityFormItemSectionHeader
                        title={fl('items_title', 'Invoice Items')}
                        onAddItem={handleCreateNewItem}
                        locked={isLocked}
                    />

                    <TransactionLineItemsTable
                        items={fields}
                        includeDiscount
                        currency={selectedCurrency}
                        onEdit={handleEditItem}
                        onRemove={remove}
                        locked={isLocked}
                    />
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
