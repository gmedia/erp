'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import SelectField from '@/components/common/SelectField';
import { InputField } from '@/components/common/InputField';
import EntityForm from '@/components/common/EntityForm';

import { ApprovalDelegation, ApprovalDelegationFormData } from '@/types/approval-delegation';
import { approvalDelegationFormSchema } from '@/utils/schemas';

interface ApprovalDelegationFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: ApprovalDelegation | null;
    onSubmit: (data: ApprovalDelegationFormData) => void;
    isLoading?: boolean;
}

const getApprovalDelegationFormDefaults = (
    entity?: ApprovalDelegation | null,
): ApprovalDelegationFormData => {
    if (!entity) {
        return {
            delegator_user_id: '',
            delegate_user_id: '',
            start_date: new Date(),
            end_date: new Date(),
            reason: '',
            is_active: '1',
            approvable_type: '',
        };
    }

    return {
        delegator_user_id: String(entity.delegator_user_id || (entity.delegator ? entity.delegator.id : '')),
        delegate_user_id: String(entity.delegate_user_id || (entity.delegate ? entity.delegate.id : '')),
        start_date: new Date(entity.start_date),
        end_date: new Date(entity.end_date),
        reason: entity.reason || '',
        is_active: entity.is_active ? '1' : '0',
        approvable_type: entity.approvable_type || '',
    };
};

export const ApprovalDelegationForm = memo<ApprovalDelegationFormProps>(function ApprovalDelegationForm({
    open,
    onOpenChange,
    entity,
    onSubmit,
    isLoading = false,
}) {
    const defaultValues = useMemo(
        () => getApprovalDelegationFormDefaults(entity),
        [entity],
    );

    const form = useForm<ApprovalDelegationFormData>({
        resolver: zodResolver(approvalDelegationFormSchema),
        defaultValues,
    });

    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    return (
        <EntityForm<ApprovalDelegationFormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={entity ? 'Edit Approval Delegation' : 'Add New Approval Delegation'}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            <div className="space-y-4">
                <span className="text-sm text-muted-foreground mt-4 mb-2 block font-medium">Delegation Details</span>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <AsyncSelectField
                        name="delegator_user_id"
                        label="Delegator"
                        url="/api/users"
                        placeholder="Select delegator..."
                    />
                    
                    <AsyncSelectField
                        name="delegate_user_id"
                        label="Delegate"
                        url="/api/users"
                        placeholder="Select delegate..."
                    />

                    <DatePickerField
                        name="start_date"
                        label="Start Date"
                        placeholder="Pick a date"
                    />

                    <DatePickerField
                        name="end_date"
                        label="End Date"
                        placeholder="Pick a date"
                    />
                </div>
                
                <span className="text-sm text-muted-foreground mt-4 mb-2 block font-medium">Additional Info</span>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <InputField
                        name="approvable_type"
                        label="Approvable Type (e.g. App\Models\PurchaseOrder)"
                        placeholder="Leave optional to delegate all types"
                    />

                    <SelectField
                        name="is_active"
                        label="Status"
                        options={[
                            { label: 'Active', value: '1' },
                            { label: 'Inactive', value: '0' }
                        ]}
                    />
                </div>
                
                <InputField
                    name="reason"
                    label="Reason"
                    placeholder="Reason for delegation..."
                />
            </div>
        </EntityForm>
    );
});
