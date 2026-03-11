'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useFieldArray, useForm } from 'react-hook-form';
import { type input } from 'zod';

import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import NameField from '@/components/common/NameField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { APPROVABLE_TYPE_OPTIONS } from '@/constants/model-options';
import { type ApprovalFlow } from '@/types/entity';
import {
    approvalFlowFormSchema,
    type ApprovalFlowFormData,
} from '@/utils/schemas';
import { ApprovalFlowStepManager } from './ApprovalFlowStepManager';

interface ApprovalFlowFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    item?: ApprovalFlow | null;
    onSubmit: (data: ApprovalFlowFormData) => void;
    isLoading?: boolean;
}

export type ApprovalFlowFormInput = input<typeof approvalFlowFormSchema>;

const getFormDefaults = (
    item?: ApprovalFlow | null,
): ApprovalFlowFormInput => {
    if (!item) {
        return {
            name: '',
            code: '',
            approvable_type: 'App\\Models\\PurchaseRequest',
            description: '',
            is_active: true,
            conditions: '',
            steps: [],
        };
    }

    return {
        name: item.name,
        code: item.code,
        approvable_type: item.approvable_type,
        description: item.description || '',
        is_active: !!item.is_active,
        conditions:
            typeof item.conditions === 'string'
                ? item.conditions
                : item.conditions
                  ? JSON.stringify(item.conditions)
                  : '',
        steps: item.steps?.length
            ? item.steps.map((step) => ({
                  id: step.id,
                  name: step.name,
                  approver_type: 'user',
                  approver_user_id:
                      step.approver_type === 'user'
                          ? step.approver_user_id
                          : null,
                  required_action: step.required_action,
                  auto_approve_after_hours: step.auto_approve_after_hours,
                  escalate_after_hours: step.escalate_after_hours,
                  escalation_user_id: step.escalation_user_id,
                  can_reject: !!step.can_reject,
              }))
            : [],
    };
};

export const ApprovalFlowForm = memo<ApprovalFlowFormProps>(
    function ApprovalFlowForm({
        open,
        onOpenChange,
        item,
        onSubmit,
        isLoading = false,
    }) {
        const defaultValues = useMemo(() => getFormDefaults(item), [item]);

        const form = useForm<ApprovalFlowFormInput, unknown, ApprovalFlowFormData>({
            resolver: zodResolver(approvalFlowFormSchema),
            defaultValues,
        });

        const stepsErrorMessage =
            typeof form.formState.errors.steps?.message === 'string'
                ? form.formState.errors.steps.message
                : undefined;

        const fieldArrayProps = useFieldArray({
            control: form.control,
            name: 'steps',
            keyName: 'fieldId',
        });

        useEffect(() => {
            form.reset(defaultValues);
        }, [form, defaultValues]);

        const handleSubmit = (data: ApprovalFlowFormData) => {
            // Parse conditions if it is valid JSON
            if (data.conditions && typeof data.conditions === 'string') {
                try {
                    data.conditions = JSON.parse(data.conditions);
                } catch {
                    // Ignore parse error, backend might reject or save it as string
                }
            }
            onSubmit(data);
        };

        return (
            <EntityForm<ApprovalFlowFormInput, ApprovalFlowFormData>
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={item ? 'Edit Approval Flow' : 'Add New Approval Flow'}
                onSubmit={handleSubmit}
                isLoading={isLoading}
                className="max-w-4xl"
            >
                <div className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <NameField
                            name="name"
                            label="Flow Name"
                            placeholder="e.g. Standard PR Flow"
                        />
                        <InputField
                            name="code"
                            label="Code"
                            placeholder="e.g. std_pr_flow"
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <SelectField
                            name="approvable_type"
                            label="Approvable Type"
                            options={[...APPROVABLE_TYPE_OPTIONS]}
                        />
                        <SelectField
                            name="is_active"
                            label="Status"
                            options={[
                                { value: 'true', label: 'Active' },
                                { value: 'false', label: 'Inactive' },
                            ]}
                        />
                    </div>

                    <TextareaField
                        name="description"
                        label="Description"
                        placeholder="Enter flow description"
                        rows={2}
                    />

                    <TextareaField
                        name="conditions"
                        label="Conditions (JSON)"
                        placeholder='e.g. {"field": "amount", "operator": ">", "value": 1000}'
                        rows={2}
                    />

                    <ApprovalFlowStepManager
                        fieldArrayProps={fieldArrayProps}
                        errorMessage={stepsErrorMessage}
                    />
                </div>
            </EntityForm>
        );
    },
);
