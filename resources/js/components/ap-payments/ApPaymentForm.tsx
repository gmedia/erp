'use client';

import { FORMAT_LOCALE } from '@/utils/formatters';
import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo, useRef } from 'react';
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
import { StatusSelectField } from '@/components/common/StatusSelectField';
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
import { type ApPayment, type ApPaymentFormData } from '@/types/ap-payment';
import { toDateInputValue } from '@/utils/date-format';
import { formatItemReference } from '@/utils/entity-form-item';
import {
    formatCurrencyByRegionalSettings,
    getRegionalNumberFormatSettings,
} from '@/utils/number-format';
import { isPostedDocumentLocked } from '@/utils/posted-document-lock';
import { apPaymentFormSchema } from '@/utils/schemas';
import { ApPaymentAllocationFormDialog } from './ApPaymentAllocationFormDialog';

interface ApPaymentFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: ApPayment | null;
    onSubmit: (data: ApPaymentFormData) => void;
    isLoading?: boolean;
}

const createEmptyApPaymentAllocation =
    (): ApPaymentFormData['allocations'][number] => ({
        supplier_bill_id: '',
        bill_label: '',
        allocated_amount: 0,
        discount_taken: 0,
        notes: '',
    });

const getApPaymentFormDefaults = (
    apPayment?: ApPayment | null,
): ApPaymentFormData => {
    if (!apPayment) {
        return {
            payment_number: '',
            supplier_id: '',
            branch_id: '',
            fiscal_year_id: '',
            payment_date: toDateInputValue(new Date()),
            payment_method: 'bank_transfer',
            bank_account_id: '',
            currency: getRegionalNumberFormatSettings().currency,
            total_amount: 0,
            reference: '',
            status: 'draft',
            notes: '',
            allocations: [],
        };
    }

    return {
        payment_number: apPayment.payment_number || '',
        supplier_id: apPayment.supplier?.id
            ? String(apPayment.supplier.id)
            : '',
        branch_id: apPayment.branch?.id ? String(apPayment.branch.id) : '',
        fiscal_year_id: apPayment.fiscal_year?.id
            ? String(apPayment.fiscal_year.id)
            : '',
        payment_date: apPayment.payment_date
            ? toDateInputValue(apPayment.payment_date)
            : toDateInputValue(new Date()),
        payment_method: apPayment.payment_method,
        bank_account_id: apPayment.bank_account?.id
            ? String(apPayment.bank_account.id)
            : '',
        currency:
            apPayment.currency ||
            getRegionalNumberFormatSettings().currency,
        total_amount: Number(apPayment.total_amount || 0),
        reference: apPayment.reference || '',
        status: apPayment.status,
        notes: apPayment.notes || '',
        allocations: (apPayment.allocations || []).length
            ? (apPayment.allocations || []).map((it) => ({
                  supplier_bill_id: it.supplier_bill_id
                      ? String(it.supplier_bill_id)
                      : '',
                  bill_label: it.bill_number || '',
                  allocated_amount: Number(it.allocated_amount || 0),
                  discount_taken: Number(it.discount_taken || 0),
                  notes: it.notes || '',
              }))
            : [],
    };
};

export const ApPaymentForm = memo<ApPaymentFormProps>(function ApPaymentForm({
    open,
    onOpenChange,
    entity,
    onSubmit,
    isLoading = false,
}) {
    const { t } = useTranslation();
    const fl = (key: string, fallback: string) => {
        const translated = t(`form_fields.ap_payments.${key}`);
        return translated === `form_fields.ap_payments.${key}`
            ? fallback
            : translated;
    };
    const isLocked = isPostedDocumentLocked(entity);
    const defaultValues = useMemo(
        () => getApPaymentFormDefaults(entity),
        [entity],
    );

    const form = useForm<ApPaymentFormData, unknown, ApPaymentFormData>({
        resolver: zodResolver(apPaymentFormSchema) as Resolver<
            ApPaymentFormData,
            unknown,
            ApPaymentFormData
        >,
        defaultValues,
    });

    const { fields, append, remove, update, replace } = useFieldArray({
        control: form.control,
        name: 'allocations',
    });
    const watchedAllocations = form.watch('allocations');
    const selectedCurrency = form.watch('currency');
    const selectedSupplierId = form.watch('supplier_id');
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

    const handleSubmit = (data: ApPaymentFormData) => {
        onSubmit({
            ...data,
            allocations: data.allocations.map((alloc) =>
                Object.fromEntries(
                    Object.entries(alloc).filter(
                        ([key]) => key !== 'bill_label',
                    ),
                ),
            ) as typeof data.allocations,
        });
    };

    useResetFormOnDefaultValues(form, defaultValues);
    const previousSupplierIdRef = useRef(selectedSupplierId);
    useEffect(() => {
        const previous = previousSupplierIdRef.current;
        previousSupplierIdRef.current = selectedSupplierId;
        if (previous && previous !== selectedSupplierId) {
            replace([]);
        }
    }, [selectedSupplierId, replace]);

    return (
        <EntityForm<ApPaymentFormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={
                entity
                    ? fl('edit_title', 'Edit AP Payment')
                    : fl('add_title', 'Add New AP Payment')
            }
            onSubmit={handleSubmit}
            isLoading={isLoading}
            className="sm:max-w-[1100px]"
        >
            <PostedDocumentLockNotice locked={isLocked} />
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <InputField
                    name="payment_number"
                    label={fl('payment_number', 'Payment Number')}
                    placeholder={fl(
                        'payment_number_placeholder',
                        'Auto-generated if empty',
                    )}
                />

                <StatusSelectField
                    fl={fl}
                    label={fl('status', 'Status')}
                    placeholder={fl('status_placeholder', 'Select status')}
                    statuses={[
                        ['draft', 'Draft'],
                        ['pending_approval', 'Pending Approval'],
                        ['confirmed', 'Confirmed'],
                        ['reconciled', 'Reconciled'],
                        ['cancelled', 'Cancelled'],
                        ['void', 'Void'],
                    ]}
                />

                <AsyncSelectField
                    name="supplier_id"
                    label={fl('supplier', 'Supplier')}
                    url="/api/suppliers"
                    placeholder={fl('supplier_placeholder', 'Select supplier')}
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

                <DatePickerField
                    name="payment_date"
                    label={fl('payment_date', 'Payment Date')}
                />

                <SelectField
                    name="payment_method"
                    label={fl('payment_method', 'Payment Method')}
                    options={[
                        {
                            value: 'bank_transfer',
                            label: fl('method_bank_transfer', 'Bank Transfer'),
                        },
                        {
                            value: 'cash',
                            label: fl('method_cash', 'Cash'),
                        },
                        {
                            value: 'check',
                            label: fl('method_check', 'Check'),
                        },
                        {
                            value: 'giro',
                            label: fl('method_giro', 'Giro'),
                        },
                        {
                            value: 'other',
                            label: fl('method_other', 'Other'),
                        },
                    ]}
                    placeholder={fl(
                        'payment_method_placeholder',
                        'Select payment method',
                    )}
                />

                <AsyncSelectField
                    name="bank_account_id"
                    label={fl('bank_account', 'Bank Account')}
                    url="/api/accounts?is_active=1&has_children=0"
                    placeholder={fl(
                        'bank_account_placeholder',
                        'Select bank account',
                    )}
                />

                <InputField
                    name="total_amount"
                    label={fl('total_amount', 'Total Amount')}
                    type="number"
                    min={0}
                    step="any"
                    placeholder="0"
                    disabled={isLocked}
                />

                <InputField
                    name="reference"
                    label={fl('reference', 'Reference')}
                    placeholder={fl(
                        'reference_placeholder',
                        'Payment reference',
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
                                    {fl('column_bill_number', 'Bill Number')}
                                </TableHead>
                                <TableHead className="w-[140px]">
                                    {fl(
                                        'column_allocated_amount',
                                        'Allocated Amount',
                                    )}
                                </TableHead>
                                <TableHead className="w-[130px]">
                                    {fl(
                                        'column_discount_taken',
                                        'Discount Taken',
                                    )}
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
                                const allocation =
                                    watchedAllocations?.[index] ??
                                    createEmptyApPaymentAllocation();

                                return (
                                    <TableRow key={field.id}>
                                        <TableCell>
                                            {formatItemReference(
                                                allocation.bill_label,
                                                allocation.supplier_bill_id,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {formatCurrencyByRegionalSettings(
                                                allocation.allocated_amount ??
                                                    0,
                                                {
                                                    locale: FORMAT_LOCALE,
                                                    currency:
                                                        selectedCurrency ||
                                                        undefined,
                                                    minimumFractionDigits: 0,
                                                    maximumFractionDigits: 2,
                                                },
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {formatCurrencyByRegionalSettings(
                                                allocation.discount_taken ?? 0,
                                                {
                                                    locale: FORMAT_LOCALE,
                                                    currency:
                                                        selectedCurrency ||
                                                        undefined,
                                                    minimumFractionDigits: 0,
                                                    maximumFractionDigits: 2,
                                                },
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {allocation.notes || '-'}
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
                                <EntityFormItemEmptyRow colSpan={5} />
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <ApPaymentAllocationFormDialog
                open={isItemDialogOpen}
                onOpenChange={handleItemDialogOpenChange}
                item={selectedItem}
                onSave={handleSaveItem}
                supplierId={selectedSupplierId}
            />
        </EntityForm>
    );
});
