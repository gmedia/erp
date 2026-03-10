'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo, useState } from 'react';
import { Controller, useFieldArray, useForm } from 'react-hook-form';
import { Pencil, Plus, Trash2 } from 'lucide-react';

import { AsyncSelect } from '@/components/common/AsyncSelect';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { type PurchaseRequest, type PurchaseRequestFormData } from '@/types/purchase-request';
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

const createEmptyPurchaseRequestItem = (): PurchaseRequestFormData['items'][number] => ({
    product_id: '',
    product_label: '',
    unit_id: '',
    unit_label: '',
    quantity: 1,
    estimated_unit_price: 0,
    notes: '',
});

const formatItemReference = (label?: string, id?: string) => {
    if (label) {
        return label;
    }

    if (id) {
        return `#${id}`;
    }

    return '-';
};

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
        branch_id: purchaseRequest.branch?.id ? String(purchaseRequest.branch.id) : '',
        department_id: purchaseRequest.department?.id ? String(purchaseRequest.department.id) : '',
        requested_by: purchaseRequest.requester?.id ? String(purchaseRequest.requester.id) : '',
        request_date: purchaseRequest.request_date ? new Date(purchaseRequest.request_date) : new Date(),
        required_date: purchaseRequest.required_date ? new Date(purchaseRequest.required_date) : null,
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

export const PurchaseRequestForm = memo<PurchaseRequestFormProps>(function PurchaseRequestForm({
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

    const form = useForm<PurchaseRequestFormData>({
        resolver: zodResolver(purchaseRequestFormSchema as any),
        defaultValues,
    });

    const { fields, append, remove, update } = useFieldArray({
        control: form.control,
        name: 'items',
    });
    const [isItemDialogOpen, setIsItemDialogOpen] = useState(false);
    const [editingIndex, setEditingIndex] = useState<number | null>(null);
    const watchedItems = form.watch('items');

    const handleCreateNewItem = () => {
        setEditingIndex(null);
        setIsItemDialogOpen(true);
    };

    const handleEditItem = (index: number) => {
        setEditingIndex(index);
        setIsItemDialogOpen(true);
    };

    const handleSubmit = (data: PurchaseRequestFormData) => {
        onSubmit({
            ...data,
            items: data.items.map(({ product_label, unit_label, ...purchaseRequestItem }) => purchaseRequestItem),
        });
    };

    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    return (
        <EntityForm<PurchaseRequestFormData>
            form={form as any}
            open={open}
            onOpenChange={onOpenChange}
            title={activePurchaseRequest ? 'Edit Purchase Request' : 'Add New Purchase Request'}
            onSubmit={handleSubmit}
            isLoading={isLoading}
            className="sm:max-w-[1000px]"
        >
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                        { value: 'pending_approval', label: 'Pending Approval' },
                        { value: 'approved', label: 'Approved' },
                        { value: 'rejected', label: 'Rejected' },
                        { value: 'partially_ordered', label: 'Partially Ordered' },
                        { value: 'fully_ordered', label: 'Fully Ordered' },
                        { value: 'cancelled', label: 'Cancelled' },
                    ]}
                    placeholder="Select status"
                />

                <Controller
                    control={form.control}
                    name="branch_id"
                    render={({ field }) => (
                        <div className="space-y-2">
                            <div className="text-sm font-medium leading-none">Branch</div>
                            <AsyncSelect
                                value={field.value ? String(field.value) : undefined}
                                onValueChange={field.onChange}
                                url="/api/branches"
                                placeholder="Select branch"
                                label="Branch"
                            />
                        </div>
                    )}
                />

                <Controller
                    control={form.control}
                    name="department_id"
                    render={({ field }) => (
                        <div className="space-y-2">
                            <div className="text-sm font-medium leading-none">Department</div>
                            <AsyncSelect
                                value={field.value ? String(field.value) : undefined}
                                onValueChange={field.onChange}
                                url="/api/departments"
                                placeholder="Select department"
                                label="Department"
                            />
                        </div>
                    )}
                />

                <Controller
                    control={form.control}
                    name="requested_by"
                    render={({ field }) => (
                        <div className="space-y-2">
                            <div className="text-sm font-medium leading-none">Requested By</div>
                            <AsyncSelect
                                value={field.value ? String(field.value) : undefined}
                                onValueChange={field.onChange}
                                url="/api/employees"
                                placeholder="Select requester"
                                label="Requested By"
                            />
                        </div>
                    )}
                />

                <DatePickerField name="request_date" label="Request Date" />
                <DatePickerField name="required_date" label="Required Date" />

                <InputField
                    name="estimated_amount"
                    label="Estimated Amount"
                    type="number"
                    placeholder="0"
                />

                <div className="md:col-span-2">
                    <TextareaField name="notes" label="Notes" placeholder="Notes" rows={2} />
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

            <div className="space-y-3 border-t pt-4 mt-2">
                <div className="flex items-center justify-between">
                    <div className="text-sm font-semibold">Items</div>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={handleCreateNewItem}
                    >
                        <Plus className="mr-2 h-4 w-4" />
                        Add Item
                    </Button>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-[180px]">Product</TableHead>
                                <TableHead className="w-[140px]">Unit</TableHead>
                                <TableHead className="w-[130px]">Quantity</TableHead>
                                <TableHead className="w-[160px]">Est. Unit Price</TableHead>
                                <TableHead>Notes</TableHead>
                                <TableHead className="w-[160px] text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {fields.map((field, index) => {
                                const purchaseRequestItem = watchedItems?.[index] || createEmptyPurchaseRequestItem();

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
                                            {purchaseRequestItem.quantity ?? 0}
                                        </TableCell>
                                        <TableCell>
                                            {purchaseRequestItem.estimated_unit_price ?? 0}
                                        </TableCell>
                                        <TableCell>
                                            {purchaseRequestItem.notes || '-'}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => handleEditItem(index)}
                                                title="Edit item"
                                                aria-label={`Edit item ${index + 1}`}
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => remove(index)}
                                                title="Remove item"
                                                aria-label={`Remove item ${index + 1}`}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                            {!fields.length && (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-6 text-center text-muted-foreground">
                                        No items added yet.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <PurchaseRequestItemFormDialog
                open={isItemDialogOpen}
                onOpenChange={(nextOpen) => {
                    setIsItemDialogOpen(nextOpen);
                    if (!nextOpen) {
                        setEditingIndex(null);
                    }
                }}
                item={editingIndex !== null ? watchedItems?.[editingIndex] || null : null}
                onSave={(data) => {
                    if (editingIndex !== null) {
                        update(editingIndex, data);
                    } else {
                        append(data);
                    }
                    setIsItemDialogOpen(false);
                    setEditingIndex(null);
                }}
            />
        </EntityForm>
    );
});
