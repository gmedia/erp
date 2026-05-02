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
    type ApPayment,
    type ApPaymentFormData,
} from '@/types/ap-payment';
import {
    formatItemReference,
    omitItemDisplayLabels,
} from '@/utils/entity-form-item';
import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';
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
            payment_date: new Date(),
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
        payment_number: apPayment.payment_number || '',
        supplier_id: apPayment.supplier?.id
            ? String(apPayment.supplier.id)
            : '',
        branch_id: apPayment.branch?.id
            ? String(apPayment.branch.id)
            : '',
        fiscal_year_id: apPayment.fiscal_year?.id
            ? String(apPayment.fiscal_year.id)
            : '',
        payment_date: apPayment.payment_date
            ? new Date(apPayment.payment_date)
            : new Date(),
        payment_method: apPayment.payment_method,
        bank_account_id: apPayment.bank_account?.id
            ? String(apPayment.bank_account.id)
            : '',
        currency: apPayment.currency || 'IDR',
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

export const ApPaymentForm = memo<ApPaymentFormProps>(
    function ApPaymentForm({
        open,
        onOpenChange,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const defaultValues = useMemo(
            () => getApPaymentFormDefaults(entity),
            [entity],
        );

        const form = useForm<
            ApPaymentFormData,
            unknown,
            ApPaymentFormData
        >({
            resolver: zodResolver(apPaymentFormSchema) as Resolver<
                ApPaymentFormData,
                unknown,
                ApPaymentFormData
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

        const handleSubmit = (data: ApPaymentFormData) => {
            onSubmit({
                ...data,
                allocations: data.allocations.map((allocation) => {
                    const { bill_label, ...rest } = allocation;
                    return rest;
                }),
            });
        };

        useResetFormOnDefaultValues(form, defaultValues);

        return (
            <EntityForm<ApPaymentFormData>
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    entity ? 'Edit AP Payment' : 'Add New AP Payment'
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
                className="sm:max-w-[1100px]"
            >
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <InputField
                        name="payment_number"
                        label="Payment Number"
                        placeholder="Auto-generated if empty"
                    />

                    <SelectField
                        name="status"
                        label="Status"
                        options={[
                            { value: 'draft', label: 'Draft' },
                            { value: 'pending_approval', label: 'Pending Approval' },
                            { value: 'confirmed', label: 'Confirmed' },
                            { value: 'reconciled', label: 'Reconciled' },
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

                    <DatePickerField name="payment_date" label="Payment Date" />

                    <SelectField
                        name="payment_method"
                        label="Payment Method"
                        options={[
                            { value: 'bank_transfer', label: 'Bank Transfer' },
                            { value: 'cash', label: 'Cash' },
                            { value: 'check', label: 'Check' },
                            { value: 'giro', label: 'Giro' },
                            { value: 'other', label: 'Other' },
                        ]}
                        placeholder="Select payment method"
                    />

                    <AsyncSelectField
                        name="bank_account_id"
                        label="Bank Account"
                        url="/api/accounts"
                        placeholder="Select bank account"
                    />

                    <InputField
                        name="currency"
                        label="Currency"
                        placeholder="IDR"
                    />

                    <InputField
                        name="total_amount"
                        label="Total Amount"
                        type="number"
                        min={0}
                        step="any"
                        placeholder="0"
                    />

                    <InputField
                        name="reference"
                        label="Reference"
                        placeholder="Payment reference"
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
                                        Bill Number
                                    </TableHead>
                                    <TableHead className="w-[140px]">
                                        Allocated Amount
                                    </TableHead>
                                    <TableHead className="w-[130px]">
                                        Discount Taken
                                    </TableHead>
                                    <TableHead>Notes</TableHead>
                                    <TableHead className="w-[120px] text-right">
                                        Action
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
                                                {formatCurrencyByRegionalSettings(
                                                    allocation.discount_taken ??
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
                                                {allocation.notes || '-'}
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
                />
            </EntityForm>
        );
    },
);