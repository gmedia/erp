'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm, useFieldArray } from 'react-hook-form';
import * as z from 'zod';

import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import NameField from '@/components/common/NameField';
import { Button } from '@/components/ui/button';
import { Plus, Trash2 } from 'lucide-react';
import { type ApprovalFlow } from './ApprovalFlowColumns';
import AsyncSelectField from '../common/AsyncSelectField';

export const approvalFlowFormSchema = z.object({
    name: z.string().min(1, 'Name is required'),
    code: z.string().min(1, 'Code is required'),
    approvable_type: z.string().min(1, 'Approvable Type is required'),
    description: z.string().nullable().optional(),
    is_active: z.union([z.boolean(), z.string()]).transform((val) => val === true || val === 'true'),
    conditions: z.string().nullable().optional(),
    steps: z.array(
        z.object({
            id: z.number().optional(),
            name: z.string().min(1, 'Step name is required'),
            approver_type: z.enum(['user', 'role', 'department_head']),
            approver_user_id: z.preprocess((val) => (val === '' || val === null ? null : Number(val)), z.number().nullable().optional()),
            approver_role_id: z.preprocess((val) => (val === '' || val === null ? null : Number(val)), z.number().nullable().optional()),
            approver_department_id: z.preprocess((val) => (val === '' || val === null ? null : Number(val)), z.number().nullable().optional()),
            required_action: z.enum(['approve', 'review', 'acknowledge']),
            auto_approve_after_hours: z.preprocess((val) => (val === '' || val === null ? null : Number(val)), z.number().nullable().optional()),
            escalate_after_hours: z.preprocess((val) => (val === '' || val === null ? null : Number(val)), z.number().nullable().optional()),
            escalation_user_id: z.preprocess((val) => (val === '' || val === null ? null : Number(val)), z.number().nullable().optional()),
            can_reject: z.union([z.boolean(), z.string()]).transform((val) => val === true || val === 'true'),
        })
    ).min(1, 'At least one step is required'),
});

export type ApprovalFlowFormData = z.infer<typeof approvalFlowFormSchema>;

interface ApprovalFlowFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    item?: ApprovalFlow | null;
    onSubmit: (data: ApprovalFlowFormData) => void;
    isLoading?: boolean;
}

const getFormDefaults = (item?: ApprovalFlow | null): any => {
    if (!item) {
        return {
            name: '',
            code: '',
            approvable_type: 'App\\Models\\PurchaseRequest',
            description: '',
            is_active: true,
            conditions: '',
            steps: [
                {
                    name: 'Step 1',
                    approver_type: 'user',
                    approver_user_id: null,
                    approver_role_id: null,
                    approver_department_id: null,
                    required_action: 'approve',
                    auto_approve_after_hours: null,
                    escalate_after_hours: null,
                    escalation_user_id: null,
                    can_reject: true,
                }
            ],
        };
    }

    return {
        name: item.name,
        code: item.code,
        approvable_type: item.approvable_type,
        description: item.description || '',
        is_active: item.is_active ? 'true' : 'false',
        conditions: typeof item.conditions === 'string' ? item.conditions : (item.conditions ? JSON.stringify(item.conditions) : ''),
        steps: item.steps?.length ? item.steps.map((step) => ({
            id: step.id,
            name: step.name,
            approver_type: step.approver_type,
            approver_user_id: step.approver_user_id,
            approver_role_id: step.approver_role_id,
            approver_department_id: step.approver_department_id,
            required_action: step.required_action,
            auto_approve_after_hours: step.auto_approve_after_hours,
            escalate_after_hours: step.escalate_after_hours,
            escalation_user_id: step.escalation_user_id,
            can_reject: step.can_reject ? 'true' : 'false',
        })) : [],
    };
};

export const ApprovalFlowForm = memo<ApprovalFlowFormProps>(function ApprovalFlowForm({
    open,
    onOpenChange,
    item,
    onSubmit,
    isLoading = false,
}) {
    const defaultValues = useMemo(() => getFormDefaults(item), [item]);

    const form = useForm<ApprovalFlowFormData>({
        resolver: zodResolver(approvalFlowFormSchema) as any,
        defaultValues,
    });

    const { fields, append, remove } = useFieldArray({
        control: form.control,
        name: 'steps',
    });

    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    const handleSubmit = (data: ApprovalFlowFormData) => {
        // Parse conditions if it is valid JSON
        if (data.conditions && typeof data.conditions === 'string') {
            try {
                data.conditions = JSON.parse(data.conditions);
            } catch (e) {
                // Ignore parse error, backend might reject or save it as string
            }
        }
        onSubmit(data);
    };

    return (
        <EntityForm<ApprovalFlowFormData>
            form={form as any}
            open={open}
            onOpenChange={onOpenChange}
            title={item ? 'Edit Approval Flow' : 'Add New Approval Flow'}
            onSubmit={handleSubmit}
            isLoading={isLoading}
            className="max-w-4xl"
        >
            <div className="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                <div className="grid grid-cols-2 gap-4">
                    <NameField name="name" label="Flow Name" placeholder="e.g. Standard PR Flow" />
                    <InputField name="code" label="Code" placeholder="e.g. std_pr_flow" />
                </div>
                
                <div className="grid grid-cols-2 gap-4">
                    <SelectField
                        name="approvable_type"
                        label="Approvable Type"
                        options={[
                            { value: 'App\\Models\\PurchaseRequest', label: 'Purchase Request' },
                            { value: 'App\\Models\\PurchaseOrder', label: 'Purchase Order' },
                            { value: 'App\\Models\\AssetMovement', label: 'Asset Movement' },
                            { value: 'App\\Models\\AssetMaintenance', label: 'Asset Maintenance' },
                            { value: 'App\\Models\\AssetStocktake', label: 'Asset Stocktake' },
                        ]}
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

                <div className="mt-8 border-t pt-4">
                    <div className="flex justify-between items-center mb-4">
                        <h3 className="text-lg font-medium">Approval Steps</h3>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => append({
                                name: `Step ${fields.length + 1}`,
                                approver_type: 'user',
                                approver_user_id: null,
                                approver_role_id: null,
                                approver_department_id: null,
                                required_action: 'approve',
                                auto_approve_after_hours: null,
                                escalate_after_hours: null,
                                escalation_user_id: null,
                                can_reject: true,
                            })}
                        >
                            <Plus className="w-4 h-4 mr-2" /> Add Step
                        </Button>
                    </div>

                    <div className="space-y-4">
                        {fields.map((field, index) => {
                            const approverType = form.watch(`steps.${index}.approver_type`);
                            
                            return (
                                <div key={field.id} className="border p-4 rounded-md shadow-sm relative space-y-4 bg-gray-50">
                                    <div className="absolute top-2 right-2">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            className="text-red-500 hover:text-red-700"
                                            onClick={() => remove(index)}
                                            disabled={fields.length === 1}
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4 pr-8">
                                        <InputField name={`steps.${index}.name`} label="Step Name" placeholder="e.g. Manager Approval" />
                                        <SelectField
                                            name={`steps.${index}.required_action`}
                                            label="Required Action"
                                            options={[
                                                { value: 'approve', label: 'Approve' },
                                                { value: 'review', label: 'Review' },
                                                { value: 'acknowledge', label: 'Acknowledge' },
                                            ]}
                                        />
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <SelectField
                                            name={`steps.${index}.approver_type`}
                                            label="Approver Type"
                                            options={[
                                                { value: 'user', label: 'Specific User' },
                                                { value: 'department_head', label: 'Department Head' },
                                                { value: 'role', label: 'Specific Role' },
                                            ]}
                                        />
                                        
                                        {approverType === 'user' && (
                                            <AsyncSelectField
                                                name={`steps.${index}.approver_user_id`}
                                                label="Select User"
                                                url="/api/users"
                                                labelFn={(user) => user.name}
                                                valueFn={(user) => String(user.id)}
                                                placeholder="Select user..."
                                            />
                                        )}
                                        {approverType === 'department_head' && (
                                            <AsyncSelectField
                                                name={`steps.${index}.approver_department_id`}
                                                label="Select Department"
                                                url="/api/departments"
                                                labelFn={(dept) => dept.name}
                                                valueFn={(dept) => String(dept.id)}
                                                placeholder="Select department..."
                                            />
                                        )}
                                        {approverType === 'role' && (
                                            <InputField
                                                name={`steps.${index}.approver_role_id`}
                                                label="Role ID"
                                                type="number"
                                                placeholder="Enter Role ID"
                                            />
                                        )}
                                    </div>

                                    <div className="grid grid-cols-3 gap-4">
                                        <InputField
                                            name={`steps.${index}.auto_approve_after_hours`}
                                            label="Auto Approve After (Hours)"
                                            type="number"
                                            placeholder="e.g. 48"
                                        />
                                        <InputField
                                            name={`steps.${index}.escalate_after_hours`}
                                            label="Escalate After (Hours)"
                                            type="number"
                                            placeholder="e.g. 24"
                                        />
                                        <AsyncSelectField
                                            name={`steps.${index}.escalation_user_id`}
                                            label="Escalate To User"
                                            url="/api/users"
                                            labelFn={(user) => user.name}
                                            valueFn={(user) => String(user.id)}
                                            placeholder="Select user..."
                                        />
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>
        </EntityForm>
    );
});
