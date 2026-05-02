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
    type ArReceipt,
    type ArReceiptFormData,
} from '@/types/ar-receipt';
import {
} from '@/utils/entity-form-item';
import {
    formatCurrencyByRegionalSettings,
} from '@/utils/number-format';
import { arReceiptFormSchema } from '@/utils/schemas';
import { ArReceiptAllocationFormDialog } from './ArReceiptAllocationFormDialog';
interface ArReceiptFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    arReceipt?: ArReceipt | null;
    item?: ArReceipt | null;
    entity?: ArReceipt | null;
    onSubmit: (data: ArReceiptFormData) => void;
    isLoading?: boolean;
}
const getArReceiptFormDefaults = (
    arReceipt?: ArReceipt | null,
): ArReceiptFormData => {
    if (!arReceipt) {
        return {
            customer_id: '',
            branch_id: '',
            fiscal_year_id: '',
            receipt_date: new Date(),
            payment_method: 'bank_transfer',
            bank_account_id: '',
            currency: 'IDR',
            total_amount: 0,
            reference: '',
            status: 'draft',
            notes: '',
            allocations: [],
        };
    }
    return {
        customer_id: arReceipt.customer?.id
            ? String(arReceipt.customer.id)
            : '',
        branch_id: arReceipt.branch?.id
            ? String(arReceipt.branch.id)
            : '',
        fiscal_year_id: arReceipt.fiscal_year?.id
            ? String(arReceipt.fiscal_year.id)
            : '',
        receipt_date: arReceipt.receipt_date
            ? new Date(arReceipt.receipt_date)
            : new Date(),
        payment_method: arReceipt.payment_method,
        bank_account_id: arReceipt.bank_account?.id
            ? String(arReceipt.bank_account.id)
            : '',
        currency: arReceipt.currency || 'IDR',
        total_amount: Number(arReceipt.total_amount || 0),
        reference: arReceipt.reference || '',
        status: arReceipt.status,
        notes: arReceipt.notes || '',
        allocations: (arReceipt.allocations || []).length
            ? (arReceipt.allocations || []).map((it) => ({
                  customer_invoice_id: it.customer_invoice_id
                      ? String(it.customer_invoice_id)
                      : '',
                  invoice_label: it.invoice_number || '',
                  allocated_amount: Number(it.allocated_amount || 0),
                  discount_given: Number(it.discount_given || 0),
                  notes: it.notes || '',
              }))
            : [],
    };
};
export const ArReceiptForm = memo<ArReceiptFormProps>(
    function ArReceiptForm({
        open,
        onOpenChange,
        arReceipt,
        item,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const activeArReceipt = arReceipt || item || entity;
        const defaultValues = useMemo(
            () => getArReceiptFormDefaults(activeArReceipt),
            [activeArReceipt],
        );
        const form = useForm<
            ArReceiptFormData,
            unknown,
            ArReceiptFormData
        >({
            resolver: zodResolver(arReceiptFormSchema) as Resolver<
                ArReceiptFormData,
                unknown,
                ArReceiptFormData
            >,
            defaultValues,
        });
        const { fields, append, remove, update } = useFieldArray({
            control: form.control,
            name: 'allocations',
        });
        const watchedAllocations = form.watch('allocations');
        const selectedCurrency = form.watch('currency');
        const {
            isItemDialogOpen,
            item: selectedItem,
            handleCreateNewItem,
            handleEditItem,
            handleItemDialogOpenChange,
            handleSaveItem,
        } = useEntityFormItemDialog({
            items: watchedAllocations,
            appendItem: append,
            updateItem: update,
        });
        useResetFormOnDefaultValues(form, defaultValues);
        const arReceiptStatusOptions = [
            { value: 'draft', label: 'Draft' },
            { value: 'confirmed', label: 'Confirmed' },
            { value: 'reconciled', label: 'Reconciled' },
            { value: 'cancelled', label: 'Cancelled' },
            { value: 'void', label: 'Void' },
        ];
        const arReceiptPaymentMethodOptions = [
            { value: 'bank_transfer', label: 'Bank Transfer' },
            { value: 'cash', label: 'Cash' },
            { value: 'check', label: 'Check' },
            { value: 'giro', label: 'Giro' },
            { value: 'credit_card', label: 'Credit Card' },
            { value: 'other', label: 'Other' },
        ];
        const currencyOptions = [
            { value: 'IDR', label: 'IDR' },
            { value: 'USD', label: 'USD' },
            { value: 'EUR', label: 'EUR' },
        ];
        const handleSubmit = (data: ArReceiptFormData) => {
            onSubmit({
                ...data,
                allocations: data.allocations.map((alloc) => Object.fromEntries(
                    Object.entries(alloc).filter(([key]) => key !== 'invoice_label'),
                )) as typeof data.allocations,
            });
        };
        return (
            <>
                <EntityForm
                    form={form}
                    open={open}
                    onOpenChange={onOpenChange}
                    title={
                        activeArReceipt
                            ? 'Edit AR Receipt'
                            : 'Add AR Receipt'
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
                            name="receipt_date"
                            label="Receipt Date"
                        />
                        <SelectField
                            name="payment_method"
                            label="Payment Method"
                            options={arReceiptPaymentMethodOptions}
                            placeholder="Select Payment Method"
                        />
                        <AsyncSelectField
                            name="bank_account_id"
                            label="Bank Account"
                            placeholder="Select Bank Account"
                            url="/api/accounts"
                        />
                        <SelectField
                            name="currency"
                            label="Currency"
                            options={currencyOptions}
                            placeholder="Select Currency"
                        />
                        <InputField
                            name="total_amount"
                            label="Total Amount"
                            type="number"
                            min={0}
                            step={0.01}
                        />
                        <InputField
                            name="reference"
                            label="Reference"
                            placeholder="e.g., Bank reference number"
                        />
                        <SelectField
                            name="status"
                            label="Status"
                            options={arReceiptStatusOptions}
                            placeholder="Select Status"
                        />
                    </div>
                    <TextareaField
                        name="notes"
                        label="Notes"
                        placeholder="Additional notes..."
                    />
                    <EntityFormItemSectionHeader
                        title="Allocations"
                        onAddItem={handleCreateNewItem}
                    />
                    {fields.length === 0 ? (
                        <EntityFormItemEmptyRow
                            colSpan={4}
                            message="No allocations added yet. Click 'Add Allocation' to start."
                        />
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12">#</TableHead>
                                    <TableHead>Invoice</TableHead>
                                    <TableHead className="text-right">
                                        Allocated Amount
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Discount Given
                                    </TableHead>
                                    <TableHead className="w-20">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {fields.map((field, index) => (
                                    <TableRow key={field.id}>
                                        <TableCell className="font-mono text-xs text-muted-foreground">
                                            {String(index + 1)}
                                        </TableCell>
                                        <TableCell>
                                            <div className="font-medium">
                                                {field.invoice_label || 'No Invoice'}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatCurrencyByRegionalSettings(
                                                field.allocated_amount,
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
                                            {formatCurrencyByRegionalSettings(
                                                field.discount_given || 0,
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
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </EntityForm>
                <ArReceiptAllocationFormDialog
                    open={isItemDialogOpen}
                    onOpenChange={handleItemDialogOpenChange}
                    item={selectedItem}
                    onSave={handleSaveItem}
                />
            </>
        );
    },
);