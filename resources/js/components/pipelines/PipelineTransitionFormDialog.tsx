import React, { useEffect, useMemo } from 'react';
import { useForm, useFieldArray } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { 
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Form } from '@/components/ui/form';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { PipelineTransitionFormData, pipelineTransitionFormSchema } from '@/utils/schemas';
import { PipelineState, PipelineTransition } from '@/types/pipeline';
import { usePipelineTransition } from '@/hooks/usePipelineTransition';
import { Card, CardContent } from '@/components/ui/card';
import { Trash, Plus } from 'lucide-react';

interface PipelineTransitionFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    pipelineId: number;
    transition: PipelineTransition | null;
    states: PipelineState[];
    onSuccess: () => void;
}

export function PipelineTransitionFormDialog({
    open,
    onOpenChange,
    pipelineId,
    transition,
    states,
    onSuccess
}: PipelineTransitionFormDialogProps) {
    const { createTransition, updateTransition } = usePipelineTransition(pipelineId);

    const defaultValues: PipelineTransitionFormData = useMemo(() => {
        if (!transition) {
            return {
                from_state_id: states[0]?.id || 0,
                to_state_id: states[0]?.id || 0,
                name: '',
                code: '',
                description: '',
                required_permission: '',
                guard_conditions: '',
                requires_confirmation: false,
                requires_comment: false,
                requires_approval: false,
                sort_order: 0,
                is_active: true,
                actions: [],
            };
        }

        return {
            from_state_id: transition.from_state_id,
            to_state_id: transition.to_state_id,
            name: transition.name,
            code: transition.code,
            description: transition.description || '',
            required_permission: transition.required_permission || '',
            guard_conditions: transition.guard_conditions ? JSON.stringify(transition.guard_conditions, null, 2) : '',
            requires_confirmation: transition.requires_confirmation,
            requires_comment: transition.requires_comment,
            requires_approval: transition.requires_approval,
            sort_order: transition.sort_order,
            is_active: transition.is_active,
            actions: transition.actions?.map(a => ({
                id: a.id,
                action_type: a.action_type,
                execution_order: a.execution_order,
                config: a.config ? JSON.stringify(a.config, null, 2) : '',
                is_async: a.is_async,
                on_failure: a.on_failure,
                is_active: a.is_active,
            })) || [],
        };
    }, [transition, states]);

    const form = useForm<PipelineTransitionFormData>({
        resolver: zodResolver(pipelineTransitionFormSchema) as any,
        defaultValues,
    });

    const { fields: actionFields, append: appendAction, remove: removeAction } = useFieldArray({
        control: form.control,
        name: 'actions'
    });

    useEffect(() => {
        if (open) {
            form.reset(defaultValues);
        }
    }, [open, defaultValues, form]);

    const stateOptions = states.map(s => ({
        value: String(s.id),
        label: s.name,
    }));

    const onSubmit = async (data: PipelineTransitionFormData) => {
        const payload = {
            ...data,
            guard_conditions: data.guard_conditions ? JSON.parse(data.guard_conditions) : null,
            actions: data.actions?.map(a => ({
                ...a,
                config: a.config ? JSON.parse(a.config) : {},
            })) || [],
        };

        const success = transition
            ? await updateTransition(transition.id, payload as any)
            : await createTransition(payload as any);

        if (success) {
            onSuccess();
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{transition ? 'Edit Transition' : 'New Transition'}</DialogTitle>
                </DialogHeader>

                <Form {...(form as any)}>
                    <form onSubmit={(e) => {
                        e.stopPropagation();
                        form.handleSubmit(onSubmit as any)(e);
                    }} className="space-y-6">
                        <Tabs defaultValue="details">
                            <TabsList className="mb-4">
                                <TabsTrigger value="details">Details</TabsTrigger>
                                <TabsTrigger value="actions">Actions ({actionFields.length})</TabsTrigger>
                            </TabsList>
                            
                            <TabsContent value="details" className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <SelectField
                                        name="from_state_id"
                                        label="From State"
                                        options={stateOptions}
                                    />
                                    <SelectField
                                        name="to_state_id"
                                        label="To State"
                                        options={stateOptions}
                                    />
                                </div>
                                
                                <div className="grid grid-cols-2 gap-4">
                                    <InputField name="name" label="Name (e.g. Approve)" />
                                    <InputField name="code" label="Code (e.g. approve_order)" />
                                </div>

                                <TextareaField name="description" label="Description" rows={2} />
                                
                                <div className="grid grid-cols-2 gap-4">
                                    <InputField name="required_permission" label="Required Permission" placeholder="e.g. order.approve" />
                                    <InputField name="sort_order" label="Sort Order" type="number" />
                                </div>

                                <TextareaField name="guard_conditions" label="Guard Conditions (JSON)" rows={3} placeholder='{"amount": {"lt": 1000}}' />
                                
                                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                                    <SelectField
                                        name="requires_confirmation"
                                        label="Confirmation"
                                        options={[{ value: 'true', label: 'Yes' }, { value: 'false', label: 'No' }]}
                                    />
                                    <SelectField
                                        name="requires_comment"
                                        label="Comment"
                                        options={[{ value: 'true', label: 'Yes' }, { value: 'false', label: 'No' }]}
                                    />
                                    <SelectField
                                        name="requires_approval"
                                        label="Approval"
                                        options={[{ value: 'true', label: 'Yes' }, { value: 'false', label: 'No' }]}
                                    />
                                    <SelectField
                                        name="is_active"
                                        label="Active"
                                        options={[{ value: 'true', label: 'Yes' }, { value: 'false', label: 'No' }]}
                                    />
                                </div>
                            </TabsContent>
                            
                            <TabsContent value="actions" className="space-y-4">
                                <div className="flex justify-between items-center bg-muted p-2 rounded">
                                    <p className="text-sm text-muted-foreground">Actions execute side effects when this transition occurs.</p>
                                    <Button type="button" size="sm" onClick={() => appendAction({
                                        action_type: 'update_field',
                                        execution_order: (actionFields.length + 1) * 10,
                                        config: '{\n  "field": "status",\n  "value": "approved"\n}',
                                        is_async: false,
                                        on_failure: 'abort',
                                        is_active: true
                                    })}>
                                        <Plus className="w-4 h-4 mr-1" /> Add Action
                                    </Button>
                                </div>

                                <div className="space-y-4 max-h-[400px] overflow-y-auto p-1">
                                    {actionFields.map((field, index) => (
                                        <Card key={field.id}>
                                            <CardContent className="pt-4 pb-2 relative">
                                                <Button 
                                                    type="button" 
                                                    variant="ghost" 
                                                    size="icon" 
                                                    className="absolute top-2 right-2 text-red-500"
                                                    onClick={() => removeAction(index)}
                                                >
                                                    <Trash className="w-4 h-4" />
                                                </Button>
                                                
                                                <div className="grid grid-cols-2 gap-4 mb-4 pr-8">
                                                    <SelectField
                                                        name={`actions.${index}.action_type`}
                                                        label="Action Type"
                                                        options={[
                                                            { value: 'update_field', label: 'Update Field' },
                                                            { value: 'create_record', label: 'Create Record' },
                                                            { value: 'send_notification', label: 'Send Notification' },
                                                            { value: 'dispatch_job', label: 'Dispatch Job' },
                                                            { value: 'trigger_approval', label: 'Trigger Approval' },
                                                            { value: 'webhook', label: 'Webhook' },
                                                            { value: 'custom', label: 'Custom' },
                                                        ]}
                                                    />
                                                    <div className="grid grid-cols-2 gap-2">
                                                        <InputField name={`actions.${index}.execution_order`} label="Order" type="number" />
                                                        <SelectField
                                                            name={`actions.${index}.on_failure`}
                                                            label="On Failure"
                                                            options={[
                                                                { value: 'abort', label: 'Abort' },
                                                                { value: 'continue', label: 'Continue' },
                                                                { value: 'log_and_continue', label: 'Log & Continue' }
                                                            ]}
                                                        />
                                                    </div>
                                                </div>

                                                <TextareaField 
                                                    name={`actions.${index}.config`} 
                                                    label="Configuration (JSON)" 
                                                    rows={4} 
                                                    className="font-mono text-sm"
                                                />
                                            </CardContent>
                                        </Card>
                                    ))}
                                </div>
                            </TabsContent>
                        </Tabs>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
                            <Button type="submit">Save Transition</Button>
                        </DialogFooter>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    );
}
