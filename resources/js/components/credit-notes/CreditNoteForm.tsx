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
    type CreditNote,
    type CreditNoteFormData,
} from '@/types/credit-note';
import {
    formatItemReference,
    omitItemDisplayLabels,
} from '@/utils/entity-form-item';
import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';
import { creditNoteFormSchema } from '@/utils/schemas';
import { CreditNoteItemFormDialog } from './CreditNoteItemFormDialog';
interface CreditNoteFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    creditNote?: CreditNote | null;
    item?: CreditNote | null;
    entity?: CreditNote | null;
    onSubmit: (data: CreditNoteFormData) => void;
    isLoading?: boolean;
}
const createEmptyCreditNoteItem =
    (): CreditNoteFormData['items'][number] => ({
        product_id: '',
        product_label: '',
        account_id: '',
        account_label: '',
        description: '',
        quantity: 1,
        unit_price: 0,
        tax_percent: 0,
        notes: '',
    });
const getCreditNoteFormDefaults = (
    creditNote?: CreditNote | null,
): CreditNoteFormData => {
    if (!creditNote) {
        return {
            customer_id: '',
            customer_invoice_id: '',
            branch_id: '',
            fiscal_year_id: '',
            credit_note_date: new Date(),
            reason: 'return',
            status: 'draft',
            notes: '',
            items: [],
        };
    }
    return {
        customer_id: creditNote.customer?.id
            ? String(creditNote.customer.id)
            : '',
        customer_invoice_id: creditNote.customer_invoice?.id
            ? String(creditNote.customer_invoice.id)
            : '',
        branch_id: creditNote.branch?.id
            ? String(creditNote.branch.id)
            : '',
        fiscal_year_id: creditNote.fiscal_year?.id
            ? String(creditNote.fiscal_year.id)
            : '',
        credit_note_date: creditNote.credit_note_date
            ? new Date(creditNote.credit_note_date)
            : new Date(),
        reason: creditNote.reason,
        status: creditNote.status,
        notes: creditNote.notes || '',
        items: (creditNote.items || []).length
            ? (creditNote.items || []).map((it) => ({
                  product_id: it.product_id ? String(it.product_id) : '',
                  product_label: it.product_name || '',
                  account_id: it.account_id ? String(it.account_id) : '',
                  account_label: it.account_name || '',
                  description: it.description || '',
                  quantity: Number(it.quantity || 0),
                  unit_price: Number(it.unit_price || 0),
                  tax_percent: Number(it.tax_percent || 0),
                  notes: it.notes || '',
              }))
            : [],
    };
};
export const CreditNoteForm = memo<CreditNoteFormProps>(
    function CreditNoteForm({
        open,
        onOpenChange,
        creditNote,
        item,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const activeCreditNote = creditNote || item || entity;
        const defaultValues = useMemo(
            () => getCreditNoteFormDefaults(activeCreditNote),
            [activeCreditNote],
        );
        const form = useForm<
            CreditNoteFormData,
            unknown,
            CreditNoteFormData
        >({
            resolver: zodResolver(creditNoteFormSchema) as Resolver<
                CreditNoteFormData,
                unknown,
                CreditNoteFormData
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

        useResetFormOnDefaultValues(form, defaultValues);
        const creditNoteStatusOptions = [
            { value: 'draft', label: 'Draft' },
            { value: 'confirmed', label: 'Confirmed' },
            { value: 'applied', label: 'Applied' },
            { value: 'cancelled', label: 'Cancelled' },
            { value: 'void', label: 'Void' },
        ];
        const creditNoteReasonOptions = [
            { value: 'return', label: 'Return' },
            { value: 'discount', label: 'Discount' },
            { value: 'correction', label: 'Correction' },
            { value: 'bad_debt', label: 'Bad Debt' },
            { value: 'other', label: 'Other' },
        ];
        const handleSubmit = (data: CreditNoteFormData) => {
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
                        activeCreditNote
                            ? 'Edit Credit Note'
                            : 'Add Credit Note'
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
                            name="customer_invoice_id"
                            label="Customer Invoice (Optional)"
                            placeholder="Select Customer Invoice"
                            url="/api/customer-invoices"
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
                            name="credit_note_date"
                            label="Credit Note Date"
                        />
                        <SelectField
                            name="reason"
                            label="Reason"
                            options={creditNoteReasonOptions}
                            placeholder="Select Reason"
                        />
                        <SelectField
                            name="status"
                            label="Status"
                            options={creditNoteStatusOptions}
                            placeholder="Select Status"
                        />
                    </div>
                    <TextareaField
                        name="notes"
                        label="Notes"
                        placeholder="Additional notes..."
                    />
                    <EntityFormItemSectionHeader
                        title="Credit Note Items"
                        onAddItem={handleCreateNewItem}
                    />
                    {fields.length === 0 ? (
                        <EntityFormItemEmptyRow
                            colSpan={7}
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
                                        Tax %
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Line Total
                                    </TableHead>
                                    <TableHead className="w-20">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {fields.map((field, index) => {
                                    const lineTotal =
                                        field.quantity *
                                        field.unit_price *
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
                                                        currency: 'IDR',
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2,
                                                    },
                                                )}
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
                                                        currency: 'IDR',
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
                <CreditNoteItemFormDialog
                    open={isItemDialogOpen}
                    onOpenChange={handleItemDialogOpenChange}
                    item={selectedItem}
                    onSave={handleSaveItem}
                />
            </>
        );
    },
);