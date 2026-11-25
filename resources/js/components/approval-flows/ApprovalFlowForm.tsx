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
import { useTranslation } from '@/contexts/i18n-context';
import { type ApprovalFlow } from '@/types/entity';
import {
    approvalFlowFormSchema,
    type ApprovalFlowFormData,
} from '@/utils/schemas';
import { ApprovalFlowStepManager } from './ApprovalFlowStepManager';

interface ApprovalFlowFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: ApprovalFlow | null;
    onSubmit: (data: ApprovalFlowFormData) => void;
    isLoading?: boolean;
}

export type ApprovalFlowFormInput = input<typeof approvalFlowFormSchema>;

const stringifyApprovalFlowConditions = (
    conditions: ApprovalFlow['conditions'],
) => {
    if (typeof conditions === 'string') {
        return conditions;
    }

    if (conditions) {
        return JSON.stringify(conditions);
    }

    return '';
};

const getFormDefaults = (item?: ApprovalFlow | null): ApprovalFlowFormInput => {
    if (!item) {
        return {
            name: '',
            code: '',
            approvable_type: String.raw`App\Models\PurchaseRequest`,
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
        conditions: stringifyApprovalFlowConditions(item.conditions),
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
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const { t } = useTranslation();
        const fl = (key: string, fallback: string) => {
            const translated = t(`form_fields.approval_flows.${key}`);
            return translated === `form_fields.approval_flows.${key}`
                ? fallback
                : translated;
        };
        const defaultValues = useMemo(() => getFormDefaults(entity), [entity]);

        const form = useForm<
            ApprovalFlowFormInput,
            unknown,
            ApprovalFlowFormData
        >({
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
                title={
                    entity
                        ? fl('edit_title', 'Edit Approval Flow')
                        : fl('add_title', 'Add New Approval Flow')
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
                className="max-w-4xl"
            >
                <div className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <NameField
                            name="name"
                            label={fl('name', 'Flow Name')}
                            placeholder={fl(
                                'name_placeholder',
                                'e.g. Standard PR Flow',
                            )}
                        />
                        <InputField
                            name="code"
                            label={fl('code', 'Code')}
                            placeholder={fl(
                                'code_placeholder',
                                'e.g. std_pr_flow',
                            )}
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <SelectField
                            name="approvable_type"
                            label={fl('approvable_type', 'Approvable Type')}
                            options={[...APPROVABLE_TYPE_OPTIONS]}
                        />
                        <SelectField
                            name="is_active"
                            label={fl('status', 'Status')}
                            options={[
                                {
                                    value: 'true',
                                    label: fl('status_active', 'Active'),
                                },
                                {
                                    value: 'false',
                                    label: fl('status_inactive', 'Inactive'),
                                },
                            ]}
                        />
                    </div>

                    <TextareaField
                        name="description"
                        label={fl('description', 'Description')}
                        placeholder={fl(
                            'description_placeholder',
                            'Enter flow description',
                        )}
                        rows={2}
                    />

                    <TextareaField
                        name="conditions"
                        label={fl('conditions', 'Conditions (JSON)')}
                        placeholder={fl(
                            'conditions_placeholder',
                            'e.g. {"field": "amount", "operator": ">", "value": 1000}',
                        )}
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
