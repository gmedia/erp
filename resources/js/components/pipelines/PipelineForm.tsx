'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';

import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import NameField from '@/components/common/NameField';

import { Pipeline } from '@/types/entity';
import { PipelineFormData, pipelineFormSchema } from '@/utils/schemas';

interface PipelineFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    pipeline?: Pipeline | null;
    onSubmit: (data: PipelineFormData) => void;
    isLoading?: boolean;
}

const renderBasicInfoSection = () => (
    <>
        <NameField name="name" label="Pipeline Name" placeholder="e.g. Asset Lifecycle" />
        <InputField name="code" label="Code" placeholder="e.g. asset_lifecycle" />
        <SelectField
            name="entity_type"
            label="Entity Type"
            options={[
                { value: 'App\\Models\\Asset', label: 'Asset' },
                { value: 'App\\Models\\PurchaseOrder', label: 'Purchase Order' },
                { value: 'App\\Models\\PurchaseRequest', label: 'Purchase Request' },
                { value: 'App\\Models\\JournalEntry', label: 'Journal Entry' },
            ]}
        />
        <InputField name="version" label="Version" type="number" placeholder="1" />
    </>
);

const renderDetailsSection = () => (
    <>
        <TextareaField
            name="description"
            label="Description"
            placeholder="Enter pipeline description"
            rows={2}
        />
        <TextareaField
            name="conditions"
            label="Conditions (JSON)"
            placeholder='e.g. {"asset_type": "vehicle"}'
            rows={3}
        />
        <SelectField
            name="is_active"
            label="Status"
            options={[
                { value: 'true', label: 'Active' },
                { value: 'false', label: 'Inactive' },
            ]}
        />
    </>
);

const getPipelineFormDefaults = (pipeline?: Pipeline | null): PipelineFormData => {
    if (!pipeline) {
        return {
            name: '',
            code: '',
            entity_type: 'App\\Models\\Asset',
            description: '',
            version: '1',
            is_active: true,
            conditions: '',
        };
    }

    return {
        name: pipeline.name,
        code: pipeline.code,
        entity_type: pipeline.entity_type,
        description: pipeline.description || '',
        version: String(pipeline.version),
        is_active: pipeline.is_active,
        conditions: pipeline.conditions || '',
    };
};

export const PipelineForm = memo<PipelineFormProps>(function PipelineForm({
    open,
    onOpenChange,
    pipeline,
    onSubmit,
    isLoading = false,
}) {
    const defaultValues = useMemo(
        () => getPipelineFormDefaults(pipeline),
        [pipeline],
    );

    const form = useForm<PipelineFormData>({
        resolver: zodResolver(pipelineFormSchema) as any,
        defaultValues,
    });

    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    // Convert boolean string back to actual boolean on submit
    const handleSubmit = (data: any) => {
        const payload = {
            ...data,
            is_active: data.is_active === 'true' || data.is_active === true,
        };
        onSubmit(payload);
    };

    return (
        <EntityForm<PipelineFormData>
            form={form as any}
            open={open}
            onOpenChange={onOpenChange}
            title={pipeline ? 'Edit Pipeline' : 'Add New Pipeline'}
            onSubmit={handleSubmit}
            isLoading={isLoading}
        >
            {renderBasicInfoSection()}
            {renderDetailsSection()}
        </EntityForm>
    );
});
