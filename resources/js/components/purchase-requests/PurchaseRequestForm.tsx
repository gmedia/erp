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
import { Alert, AlertDescription } from '@/components/ui/alert';
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
    type PurchaseRequest,
    type PurchaseRequestFormData,
} from '@/types/purchase-request';
import { toDateInputValue } from '@/utils/date-format';
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
            request_date: toDateInputValue(new Date()),
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
            ? toDateInputValue(purchaseRequest.request_date)
            : toDateInputValue(new Date()),
        required_date: purchaseRequest.required_date
            ? toDateInputValue(purchaseRequest.required_date)
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
        const isLocked = Boolean(
            activePurchaseRequest && activePurchaseRequest.status !== 'draft',
        );
        const { t } = useTranslation();
        const fl = (key: string, fallback: string) => {
            const translated = t(`form_fields.purchase_requests.${key}`);
            return translated === `form_fields.purchase_requests.${key}`
                ? fallback
                : translated;
        };
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
                        ? fl('edit_title', 'Edit Purchase Request')
                        : fl('add_title', 'Add New Purchase Request')
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
                submitDisabled={isLocked}
                className="sm:max-w-[1000px]"
            >
                {isLocked && (
                    <Alert>
                        <AlertDescription>
                            {t('approval.form_locked')}
                        </AlertDescription>
                    </Alert>
                )}
                <fieldset disabled={isLocked} className="contents">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <InputField
                            name="pr_number"
                            label={fl('pr_number', 'PR Number')}
                            placeholder={fl(
                                'pr_number_placeholder',
                                'Auto-generated if empty',
                            )}
                        />

                        <SelectField
                            name="priority"
                            label={fl('priority', 'Priority')}
                            options={[
                                {
                                    value: 'low',
                                    label: fl('priority_low', 'Low'),
                                },
                                {
                                    value: 'normal',
                                    label: fl('priority_normal', 'Normal'),
                                },
                                {
                                    value: 'high',
                                    label: fl('priority_high', 'High'),
                                },
                                {
                                    value: 'urgent',
                                    label: fl('priority_urgent', 'Urgent'),
                                },
                            ]}
                            placeholder={fl(
                                'priority_placeholder',
                                'Select priority',
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
                                    value: 'pending_approval',
                                    label: fl(
                                        'status_pending_approval',
                                        'Pending Approval',
                                    ),
                                },
                                ...(purchaseRequest?.status === 'approved'
                                    ? [
                                          {
                                              value: 'approved',
                                              label: fl(
                                                  'status_approved',
                                                  'Approved',
                                              ),
                                          },
                                      ]
                                    : []),
                                ...(purchaseRequest?.status === 'rejected'
                                    ? [
                                          {
                                              value: 'rejected',
                                              label: fl(
                                                  'status_rejected',
                                                  'Rejected',
                                              ),
                                          },
                                      ]
                                    : []),
                                {
                                    value: 'partially_ordered',
                                    label: fl(
                                        'status_partially_ordered',
                                        'Partially Ordered',
                                    ),
                                },
                                {
                                    value: 'fully_ordered',
                                    label: fl(
                                        'status_fully_ordered',
                                        'Fully Ordered',
                                    ),
                                },
                                {
                                    value: 'cancelled',
                                    label: fl('status_cancelled', 'Cancelled'),
                                },
                            ]}
                            placeholder={fl(
                                'status_placeholder',
                                'Select status',
                            )}
                        />

                        <AsyncSelectField
                            name="branch_id"
                            label={fl('branch', 'Branch')}
                            url="/api/branches"
                            placeholder={fl(
                                'branch_placeholder',
                                'Select branch',
                            )}
                        />

                        <AsyncSelectField
                            name="department_id"
                            label={fl('department', 'Department')}
                            url="/api/departments"
                            placeholder={fl(
                                'department_placeholder',
                                'Select department',
                            )}
                        />

                        <AsyncSelectField
                            name="requested_by"
                            label={fl('requested_by', 'Requested By')}
                            url="/api/employees"
                            placeholder={fl(
                                'requested_by_placeholder',
                                'Select requester',
                            )}
                        />

                        <DatePickerField
                            name="request_date"
                            label={fl('request_date', 'Request Date')}
                        />
                        <DatePickerField
                            name="required_date"
                            label={fl('required_date', 'Required Date')}
                        />

                        <InputField
                            name="estimated_amount"
                            label={fl('estimated_amount', 'Estimated Amount')}
                            type="number"
                            placeholder="0"
                        />

                        <div className="md:col-span-2">
                            <TextareaField
                                name="notes"
                                label={fl('notes', 'Notes')}
                                placeholder={fl('notes_placeholder', 'Notes')}
                                rows={2}
                            />
                        </div>

                        <div className="md:col-span-2">
                            <TextareaField
                                name="rejection_reason"
                                label={fl(
                                    'rejection_reason',
                                    'Rejection Reason',
                                )}
                                placeholder={fl(
                                    'rejection_reason_placeholder',
                                    'Reason if rejected',
                                )}
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
                                            {fl('item_product', 'Product')}
                                        </TableHead>
                                        <TableHead className="w-[140px]">
                                            {fl('item_unit', 'Unit')}
                                        </TableHead>
                                        <TableHead className="w-[130px]">
                                            {fl('item_quantity', 'Quantity')}
                                        </TableHead>
                                        <TableHead className="w-[160px]">
                                            {fl(
                                                'item_estimated_unit_price',
                                                'Est. Unit Price',
                                            )}
                                        </TableHead>
                                        <TableHead>
                                            {fl('item_notes', 'Notes')}
                                        </TableHead>
                                        <TableHead className="w-[160px] text-right">
                                            {fl('item_action', 'Action')}
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
                                                            minimumFractionDigits: 0,
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
                </fieldset>

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
