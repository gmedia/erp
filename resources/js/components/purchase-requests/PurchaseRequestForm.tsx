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
    type PurchaseRequest,
    type PurchaseRequestFormData,
} from '@/types/purchase-request';
import {
    formatItemReference,
    omitItemDisplayLabels,
} from '@/utils/entity-form-item';
import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';
import { purchaseRequestFormSchema } from '@/utils/schemas';
import { PurchaseRequestItemFormDialog } from './PurchaseRequestItemFormDialog';

interface PurchaseRequestFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    purchaseRequest?: PurchaseRequest | null;
    item?: PurchaseRequest | null;
    entity?: PurchaseRequest | null;
    onSubmit: (data: PurchaseRequestFormData) => void;
    isLoading?: boolean;
}

const createEmptyPurchaseRequestItem =
    (): PurchaseRequestFormData['items'][number] => ({
        product_id: '',
        product_label: '',
        unit_id: '',
        unit_label: '',
        quantity: 1,
        estimated_unit_price: 0,
        notes: '',
    });

const getPurchaseRequestFormDefaults = (
    purchaseRequest?: PurchaseRequest | null,
): PurchaseRequestFormData => {
    if (!purchaseRequest) {
        return {
            pr_number: '',
            branch_id: '',
            department_id: '',
            requested_by: '',
            request_date: new Date(),
            required_date: null,
            priority: 'normal',
            status: 'draft',
            estimated_amount: 0,
            notes: '',
            rejection_reason: '',
            items: [],
        };
    }

    return {
        pr_number: purchaseRequest.pr_number || '',
        branch_id: purchaseRequest.branch?.id
            ? String(purchaseRequest.branch.id)
            : '',
        department_id: purchaseRequest.department?.id
            ? String(purchaseRequest.department.id)
            : '',
        requested_by: purchaseRequest.requester?.id
            ? String(purchaseRequest.requester.id)
            : '',
        request_date: purchaseRequest.request_date
            ? new Date(purchaseRequest.request_date)
            : new Date(),
        required_date: purchaseRequest.required_date
            ? new Date(purchaseRequest.required_date)
            : null,
        priority: purchaseRequest.priority,
        status: purchaseRequest.status,
        estimated_amount: Number(purchaseRequest.estimated_amount || 0),
        notes: purchaseRequest.notes || '',
        rejection_reason: purchaseRequest.rejection_reason || '',
        items: (purchaseRequest.items || []).length
            ? (purchaseRequest.items || []).map((it) => ({
                  product_id: it.product?.id ? String(it.product.id) : '',
                  product_label: it.product?.name || '',
                  unit_id: it.unit?.id ? String(it.unit.id) : '',
                  unit_label: it.unit?.name || '',
                  quantity: Number(it.quantity || 0),
                  estimated_unit_price: Number(it.estimated_unit_price || 0),
                  notes: it.notes || '',
              }))
            : [],
    };
};

export const PurchaseRequestForm = memo<PurchaseRequestFormProps>(
    function PurchaseRequestForm({
        open,
        onOpenChange,
        purchaseRequest,
        item,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const activePurchaseRequest = purchaseRequest || item || entity;
        const defaultValues = useMemo(
            () => getPurchaseRequestFormDefaults(activePurchaseRequest),
            [activePurchaseRequest],
        );

        const form = useForm<
            PurchaseRequestFormData,
            unknown,
            PurchaseRequestFormData
        >({
            resolver: zodResolver(purchaseRequestFormSchema) as Resolver<
                PurchaseRequestFormData,
                unknown,
                PurchaseRequestFormData
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

        const handleSubmit = (data: PurchaseRequestFormData) => {
            onSubmit({
                ...data,
                items: data.items.map(omitItemDisplayLabels),
            });
        };

        useResetFormOnDefaultValues(form, defaultValues);

        return (
            <EntityForm<PurchaseRequestFormData>
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    activePurchaseRequest
                        ? 'Edit Purchase Request'
                        : 'Add New Purchase Request'
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
                className="sm:max-w-[1000px]"
            >
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <InputField
                        name="pr_number"
                        label="PR Number"
                        placeholder="Auto-generated if empty"
                    />

                    <SelectField
                        name="priority"
                        label="Priority"
                        options={[
                            { value: 'low', label: 'Low' },
                            { value: 'normal', label: 'Normal' },
                            { value: 'high', label: 'High' },
                            { value: 'urgent', label: 'Urgent' },
                        ]}
                        placeholder="Select priority"
                    />

                    <SelectField
                        name="status"
                        label="Status"
                        options={[
                            { value: 'draft', label: 'Draft' },
                            {
                                value: 'pending_approval',
                                label: 'Pending Approval',
                            },
                            { value: 'approved', label: 'Approved' },
                            { value: 'rejected', label: 'Rejected' },
                            {
                                value: 'partially_ordered',
                                label: 'Partially Ordered',
                            },
                            { value: 'fully_ordered', label: 'Fully Ordered' },
                            { value: 'cancelled', label: 'Cancelled' },
                        ]}
                        placeholder="Select status"
                    />

                    <AsyncSelectField
                        name="branch_id"
                        label="Branch"
                        url="/api/branches"
                        placeholder="Select branch"
                    />

                    <AsyncSelectField
                        name="department_id"
                        label="Department"
                        url="/api/departments"
                        placeholder="Select department"
                    />

                    <AsyncSelectField
                        name="requested_by"
                        label="Requested By"
                        url="/api/employees"
                        placeholder="Select requester"
                    />

                    <DatePickerField name="request_date" label="Request Date" />
                    <DatePickerField
                        name="required_date"
                        label="Required Date"
                    />

                    <InputField
                        name="estimated_amount"
                        label="Estimated Amount"
                        type="number"
                        placeholder="0"
                    />

                    <div className="md:col-span-2">
                        <TextareaField
                            name="notes"
                            label="Notes"
                            placeholder="Notes"
                            rows={2}
                        />
                    </div>

                    <div className="md:col-span-2">
                        <TextareaField
                            name="rejection_reason"
                            label="Rejection Reason"
                            placeholder="Reason if rejected"
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
                                    <TableHead className="w-[180px]">
                                        Product
                                    </TableHead>
                                    <TableHead className="w-[140px]">
                                        Unit
                                    </TableHead>
                                    <TableHead className="w-[130px]">
                                        Quantity
                                    </TableHead>
                                    <TableHead className="w-[160px]">
                                        Est. Unit Price
                                    </TableHead>
                                    <TableHead>Notes</TableHead>
                                    <TableHead className="w-[160px] text-right">
                                        Action
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {fields.map((field, index) => {
                                    const purchaseRequestItem =
                                        watchedItems?.[index] ??
                                        createEmptyPurchaseRequestItem();

                                    return (
                                        <TableRow key={field.id}>
                                            <TableCell>
                                                {formatItemReference(
                                                    purchaseRequestItem.product_label,
                                                    purchaseRequestItem.product_id,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {formatItemReference(
                                                    purchaseRequestItem.unit_label,
                                                    purchaseRequestItem.unit_id,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {formatNumberByRegionalSettings(
                                                    purchaseRequestItem.quantity ??
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
                                                    purchaseRequestItem.estimated_unit_price ??
                                                        0,
                                                    {
                                                        locale: 'id-ID',
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2,
                                                    },
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {purchaseRequestItem.notes ||
                                                    '-'}
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
                                    <EntityFormItemEmptyRow colSpan={6} />
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </div>

                <PurchaseRequestItemFormDialog
                    open={isItemDialogOpen}
                    onOpenChange={handleItemDialogOpenChange}
                    item={selectedItem}
                    onSave={handleSaveItem}
                />
            </EntityForm>
        );
    },
);
