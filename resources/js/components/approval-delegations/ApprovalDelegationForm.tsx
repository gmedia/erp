'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { useTranslation } from '@/contexts/i18n-context';

import {
    ApprovalDelegation,
    ApprovalDelegationFormData,
} from '@/types/approval-delegation';
import { toDateInputValue } from '@/utils/date-format';
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
): z.input<typeof approvalDelegationFormSchema> => {
    if (!entity) {
        return {
            delegator_user_id: '',
            delegate_user_id: '',
            start_date: toDateInputValue(new Date()),
            end_date: toDateInputValue(new Date()),
            reason: '',
            is_active: '1',
            approvable_type: '',
        };
    }

    return {
        delegator_user_id: String(
            entity.delegator_user_id ||
                (entity.delegator ? entity.delegator.id : ''),
        ),
        delegate_user_id: String(
            entity.delegate_user_id ||
                (entity.delegate ? entity.delegate.id : ''),
        ),
        start_date: toDateInputValue(entity.start_date),
        end_date: toDateInputValue(entity.end_date),
        reason: entity.reason || '',
        is_active: entity.is_active ? '1' : '0',
        approvable_type: entity.approvable_type || '',
    };
};

export const ApprovalDelegationForm = memo<ApprovalDelegationFormProps>(
    function ApprovalDelegationForm({
        open,
        onOpenChange,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const { t } = useTranslation();
        const fl = (key: string, fallback: string) => {
            const translated = t(`form_fields.approval_delegations.${key}`);
            return translated === `form_fields.approval_delegations.${key}`
                ? fallback
                : translated;
        };
        const defaultValues = useMemo(
            () => getApprovalDelegationFormDefaults(entity),
            [entity],
        );

        type ApprovalDelegationFormInput = z.input<
            typeof approvalDelegationFormSchema
        >;

        const form = useForm<
            ApprovalDelegationFormInput,
            unknown,
            ApprovalDelegationFormData
        >({
            resolver: zodResolver(approvalDelegationFormSchema),
            defaultValues,
        });

        useEffect(() => {
            form.reset(defaultValues);
        }, [form, defaultValues]);

        return (
            <EntityForm<ApprovalDelegationFormInput, ApprovalDelegationFormData>
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    entity
                        ? fl('edit_title', 'Edit Approval Delegation')
                        : fl('add_title', 'Add New Approval Delegation')
                }
                onSubmit={onSubmit}
                isLoading={isLoading}
            >
                <div className="space-y-4">
                    <span className="mt-4 mb-2 block text-sm font-medium text-muted-foreground">
                        {fl('details_title', 'Delegation Details')}
                    </span>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <AsyncSelectField
                            name="delegator_user_id"
                            label={fl('delegator', 'Delegator')}
                            url="/api/users"
                            placeholder={fl(
                                'delegator_placeholder',
                                'Select delegator...',
                            )}
                        />

                        <AsyncSelectField
                            name="delegate_user_id"
                            label={fl('delegate', 'Delegate')}
                            url="/api/users"
                            placeholder={fl(
                                'delegate_placeholder',
                                'Select delegate...',
                            )}
                        />

                        <DatePickerField
                            name="start_date"
                            label={fl('start_date', 'Start Date')}
                            placeholder={fl('date_placeholder', 'Pick a date')}
                        />

                        <DatePickerField
                            name="end_date"
                            label={fl('end_date', 'End Date')}
                            placeholder={fl('date_placeholder', 'Pick a date')}
                        />
                    </div>

                    <span className="mt-4 mb-2 block text-sm font-medium text-muted-foreground">
                        {fl('additional_title', 'Additional Info')}
                    </span>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <InputField
                            name="approvable_type"
                            label={fl(
                                'approvable_type',
                                'Approvable Type (e.g. App\\Models\\PurchaseOrder)',
                            )}
                            placeholder={fl(
                                'approvable_type_placeholder',
                                'All types (optional)',
                            )}
                        />

                        <SelectField
                            name="is_active"
                            label={fl('status', 'Status')}
                            options={[
                                {
                                    label: fl('status_active', 'Active'),
                                    value: '1',
                                },
                                {
                                    label: fl('status_inactive', 'Inactive'),
                                    value: '0',
                                },
                            ]}
                        />
                    </div>

                    <InputField
                        name="reason"
                        label={fl('reason', 'Reason')}
                        placeholder={fl(
                            'reason_placeholder',
                            'Reason for delegation...',
                        )}
                    />
                </div>
            </EntityForm>
        );
    },
);
