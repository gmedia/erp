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
import { PostedDocumentLockNotice } from '@/components/common/PostedDocumentLockNotice';
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
import { useTranslation } from '@/contexts/i18n-context';
import { useEntityFormItemDialog } from '@/hooks/useEntityFormItemDialog';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import {
    type SupplierBill,
    type SupplierBillFormData,
} from '@/types/supplier-bill';
import { toDateInputValue } from '@/utils/date-format';
import {
    formatItemReference,
    omitItemDisplayLabels,
} from '@/utils/entity-form-item';
import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';
import { isPostedDocumentLocked } from '@/utils/posted-document-lock';
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
            bill_date: toDateInputValue(new Date()),
            due_date: toDateInputValue(
                new Date(Date.now() + 30 * 24 * 60 * 60 * 1000),
            ),
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
            ? toDateInputValue(supplierBill.supplier_invoice_date)
            : null,
        bill_date: supplierBill.bill_date
            ? toDateInputValue(supplierBill.bill_date)
            : toDateInputValue(new Date()),
        due_date: supplierBill.due_date
            ? toDateInputValue(supplierBill.due_date)
            : toDateInputValue(new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)),
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
        const { t } = useTranslation();
        const fl = (key: string, fallback: string) => {
            const translated = t(`form_fields.supplier_bills.${key}`);
            return translated === `form_fields.supplier_bills.${key}`
                ? fallback
                : translated;
        };
        const isLocked = isPostedDocumentLocked(entity);
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
                    entity
                        ? fl('edit_title', 'Edit Supplier Bill')
                        : fl('add_title', 'Add New Supplier Bill')
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
                className="sm:max-w-[1100px]"
            >
                <PostedDocumentLockNotice locked={isLocked} />
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <InputField
                        name="bill_number"
                        label={fl('bill_number', 'Bill Number')}
                        placeholder={fl(
                            'bill_number_placeholder',
                            'Auto-generated if empty',
                        )}
                    />

                    <SelectField
                        name="status"
                        label={fl('status', 'Status')}
                        options={[
                            {
                                value: 'draft',
                                label: fl('status_draft', 'Draft'),
                            },
                            {
                                value: 'confirmed',
                                label: fl('status_confirmed', 'Confirmed'),
                            },
                            {
                                value: 'partially_paid',
                                label: fl(
                                    'status_partially_paid',
                                    'Partially Paid',
                                ),
                            },
                            {
                                value: 'paid',
                                label: fl('status_paid', 'Paid'),
                            },
                            {
                                value: 'overdue',
                                label: fl('status_overdue', 'Overdue'),
                            },
                            {
                                value: 'cancelled',
                                label: fl('status_cancelled', 'Cancelled'),
                            },
                            {
                                value: 'void',
                                label: fl('status_void', 'Void'),
                            },
                        ]}
                        placeholder={fl('status_placeholder', 'Select status')}
                    />

                    <AsyncSelectField
                        name="supplier_id"
                        label={fl('supplier', 'Supplier')}
                        url="/api/suppliers"
                        placeholder={fl(
                            'supplier_placeholder',
                            'Select supplier',
                        )}
                    />

                    <AsyncSelectField
                        name="branch_id"
                        label={fl('branch', 'Branch')}
                        url="/api/branches"
                        placeholder={fl('branch_placeholder', 'Select branch')}
                    />

                    <AsyncSelectField
                        name="fiscal_year_id"
                        label={fl('fiscal_year', 'Fiscal Year')}
                        url="/api/fiscal-years"
                        placeholder={fl(
                            'fiscal_year_placeholder',
                            'Select fiscal year',
                        )}
                        preferredMetaKey="preferred_fiscal_year_id"
                    />

                    <AsyncSelectField
                        name="purchase_order_id"
                        label={fl('purchase_order', 'Purchase Order')}
                        url="/api/purchase-orders"
                        placeholder={fl(
                            'purchase_order_placeholder',
                            'Select purchase order',
                        )}
                    />

                    <AsyncSelectField
                        name="goods_receipt_id"
                        label={fl('goods_receipt', 'Goods Receipt')}
                        url="/api/goods-receipts"
                        placeholder={fl(
                            'goods_receipt_placeholder',
                            'Select goods receipt',
                        )}
                    />

                    <InputField
                        name="supplier_invoice_number"
                        label={fl(
                            'supplier_invoice_number',
                            'Supplier Invoice Number',
                        )}
                        placeholder={fl(
                            'supplier_invoice_number_placeholder',
                            'Supplier invoice number',
                        )}
                    />

                    <DatePickerField
                        name="supplier_invoice_date"
                        label={fl(
                            'supplier_invoice_date',
                            'Supplier Invoice Date',
                        )}
                    />

                    <DatePickerField
                        name="bill_date"
                        label={fl('bill_date', 'Bill Date')}
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
                            'Net 30',
                        )}
                    />

                    <div className="md:col-span-2">
                        <TextareaField
                            name="notes"
                            label={fl('notes', 'Notes')}
                            placeholder={fl('notes_placeholder', 'Notes')}
                            rows={2}
                        />
                    </div>
                </div>

                <div className="mt-2 space-y-3 border-t pt-4">
                    <EntityFormItemSectionHeader
                        onAddItem={handleCreateNewItem}
                        locked={isLocked}
                    />

                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[220px]">
                                        {fl('column_description', 'Description')}
                                    </TableHead>
                                    <TableHead className="w-[140px]">
                                        {fl('column_account', 'Account')}
                                    </TableHead>
                                    <TableHead className="w-[120px]">
                                        {fl('column_qty', 'Qty')}
                                    </TableHead>
                                    <TableHead className="w-[140px]">
                                        {fl('column_unit_price', 'Unit Price')}
                                    </TableHead>
                                    <TableHead className="w-[130px]">
                                        {fl('column_disc_percent', 'Disc %')}
                                    </TableHead>
                                    <TableHead className="w-[130px]">
                                        {fl('column_tax_percent', 'Tax %')}
                                    </TableHead>
                                    <TableHead>
                                        {fl('column_notes', 'Notes')}
                                    </TableHead>
                                    <TableHead className="w-[120px] text-right">
                                        {fl('column_action', 'Action')}
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
                                                        minimumFractionDigits: 0,
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
                                                locked={isLocked}
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
