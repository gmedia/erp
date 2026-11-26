'use client';
import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import { DocumentDimensionFields } from '@/components/common/DocumentDimensionFields';
import EntityForm from '@/components/common/EntityForm';
import { EntityFormItemSectionHeader } from '@/components/common/EntityFormItemTable';
import { PostedDocumentLockNotice } from '@/components/common/PostedDocumentLockNotice';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { TransactionLineItemsTable } from '@/components/common/TransactionLineItemsTable';
import { useTranslation } from '@/contexts/i18n-context';
import { useEntityFormItemDialog } from '@/hooks/useEntityFormItemDialog';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import { type CreditNote, type CreditNoteFormData } from '@/types/credit-note';
import { type CustomerInvoice } from '@/types/customer-invoice';
import { toDateInputValue } from '@/utils/date-format';
import { omitItemDisplayLabels } from '@/utils/entity-form-item';
import { isPostedDocumentLocked } from '@/utils/posted-document-lock';
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
            department_id: '',
            fiscal_year_id: '',
            credit_note_date: toDateInputValue(new Date()),
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
        department_id: creditNote.department?.id
            ? String(creditNote.department.id)
            : '',
        fiscal_year_id: creditNote.fiscal_year?.id
            ? String(creditNote.fiscal_year.id)
            : '',
        credit_note_date: creditNote.credit_note_date
            ? toDateInputValue(creditNote.credit_note_date)
            : toDateInputValue(new Date()),
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
        const { t } = useTranslation();
        const fl = (key: string, fallback: string) => {
            const translated = t(`form_fields.credit_notes.${key}`);
            return translated === `form_fields.credit_notes.${key}`
                ? fallback
                : translated;
        };
        const activeCreditNote = creditNote || item || entity;
        const isLocked = isPostedDocumentLocked(activeCreditNote);
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
            { value: 'draft', label: fl('status_draft', 'Draft') },
            { value: 'confirmed', label: fl('status_confirmed', 'Confirmed') },
            { value: 'applied', label: fl('status_applied', 'Applied') },
            { value: 'cancelled', label: fl('status_cancelled', 'Cancelled') },
            { value: 'void', label: fl('status_void', 'Void') },
        ];
        const creditNoteReasonOptions = [
            { value: 'return', label: fl('reason_return', 'Return') },
            { value: 'discount', label: fl('reason_discount', 'Discount') },
            {
                value: 'correction',
                label: fl('reason_correction', 'Correction'),
            },
            { value: 'bad_debt', label: fl('reason_bad_debt', 'Bad Debt') },
            { value: 'other', label: fl('reason_other', 'Other') },
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
                            ? fl('edit_title', 'Edit Credit Note')
                            : fl('add_title', 'Add Credit Note')
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
                        <AsyncSelectField<
                            Pick<CustomerInvoice, 'id' | 'invoice_number'>
                        >
                            name="customer_invoice_id"
                            label={fl(
                                'customer_invoice_optional',
                                'Customer Invoice (Optional)',
                            )}
                            placeholder={fl(
                                'customer_invoice_placeholder',
                                'Select Customer Invoice',
                            )}
                            url="/api/customer-invoices"
                            labelFn={(invoice) =>
                                invoice.invoice_number ?? String(invoice.id)
                            }
                            valueFn={(invoice) => String(invoice.id)}
                        />
                        <DocumentDimensionFields fl={fl} />
                        <DatePickerField
                            name="credit_note_date"
                            label={fl('credit_note_date', 'Credit Note Date')}
                        />
                        <SelectField
                            name="reason"
                            label={fl('reason', 'Reason')}
                            options={creditNoteReasonOptions}
                            placeholder={fl(
                                'reason_placeholder',
                                'Select Reason',
                            )}
                        />
                        <SelectField
                            name="status"
                            label={fl('status', 'Status')}
                            options={creditNoteStatusOptions}
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
                        title={fl('items_title', 'Credit Note Items')}
                        onAddItem={handleCreateNewItem}
                        locked={isLocked}
                    />
                    <TransactionLineItemsTable
                        items={fields}
                        onEdit={handleEditItem}
                        onRemove={remove}
                        locked={isLocked}
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
