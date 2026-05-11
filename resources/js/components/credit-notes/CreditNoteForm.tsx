'use client';
import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import { EntityFormItemSectionHeader } from '@/components/common/EntityFormItemTable';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { TransactionLineItemsTable } from '@/components/common/TransactionLineItemsTable';
import { useEntityFormItemDialog } from '@/hooks/useEntityFormItemDialog';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import { type CreditNote, type CreditNoteFormData } from '@/types/credit-note';
import { omitItemDisplayLabels } from '@/utils/entity-form-item';
import { creditNoteFormSchema } from '@/utils/schemas';
import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useMemo } from 'react';
import { type Resolver, useFieldArray, useForm } from 'react-hook-form';
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
        branch_id: creditNote.branch?.id ? String(creditNote.branch.id) : '',
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
        const form = useForm<CreditNoteFormData, unknown, CreditNoteFormData>({
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
                    className="sm:max-w-[1100px]"
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
                    <TransactionLineItemsTable
                        items={fields}
                        onEdit={handleEditItem}
                        onRemove={remove}
                    />
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
