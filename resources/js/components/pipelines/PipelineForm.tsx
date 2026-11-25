'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm, type UseFormReturn } from 'react-hook-form';
import * as z from 'zod';

import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import NameField from '@/components/common/NameField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { useTranslation } from '@/contexts/i18n-context';

import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { APPROVABLE_TYPE_OPTIONS } from '@/constants/model-options';
import { Pipeline } from '@/types/entity';
import { PipelineFormData, pipelineFormSchema } from '@/utils/schemas';
import { PipelineStateManager } from './PipelineStateManager';
import { PipelineTransitionManager } from './PipelineTransitionManager';

interface PipelineFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: Pipeline | null;
    onSubmit: (data: PipelineFormData) => void;
    isLoading?: boolean;
}

type PipelineFieldLabeler = (key: string, fallback: string) => string;

const renderBasicInfoSection = (fl: PipelineFieldLabeler) => (
    <>
        <NameField
            name="name"
            label={fl('name', 'Pipeline Name')}
            placeholder={fl('name_placeholder', 'e.g. Asset Lifecycle')}
        />
        <InputField
            name="code"
            label={fl('code', 'Code')}
            placeholder={fl('code_placeholder', 'e.g. asset_lifecycle')}
        />
        <SelectField
            name="entity_type"
            label={fl('entity_type', 'Entity Type')}
            options={[...APPROVABLE_TYPE_OPTIONS]}
        />
        <InputField
            name="version"
            label={fl('version', 'Version')}
            type="number"
            placeholder={fl('version_placeholder', '1')}
        />
    </>
);

const renderDetailsSection = (fl: PipelineFieldLabeler) => (
    <>
        <TextareaField
            name="description"
            label={fl('description', 'Description')}
            placeholder={fl(
                'description_placeholder',
                'Enter pipeline description',
            )}
            rows={2}
        />
        <TextareaField
            name="conditions"
            label={fl('conditions', 'Conditions (JSON)')}
            placeholder={fl(
                'conditions_placeholder',
                'e.g. {"asset_type": "vehicle"}',
            )}
            rows={3}
        />
        <SelectField
            name="is_active"
            label={fl('status', 'Status')}
            options={[
                { value: 'true', label: fl('status_active', 'Active') },
                { value: 'false', label: fl('status_inactive', 'Inactive') },
            ]}
        />
    </>
);

const getPipelineFormDefaults = (
    pipeline?: Pipeline | null,
): PipelineFormData => {
    if (!pipeline) {
        return {
            name: '',
            code: '',
            entity_type: String.raw`App\Models\Asset`,
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
    entity,
    onSubmit,
    isLoading = false,
}) {
    const { t } = useTranslation();
    const fl: PipelineFieldLabeler = (key, fallback) => {
        const translated = t(`form_fields.pipelines.${key}`);
        return translated === `form_fields.pipelines.${key}`
            ? fallback
            : translated;
    };
    const defaultValues = useMemo(
        () => getPipelineFormDefaults(entity),
        [entity],
    );

    const form = useForm<z.input<typeof pipelineFormSchema>>({
        resolver: zodResolver(pipelineFormSchema),
        defaultValues,
    });

    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    // Convert boolean string back to actual boolean on submit
    const handleSubmit = (data: z.input<typeof pipelineFormSchema>) => {
        const payload = {
            ...data,
            is_active: data.is_active === 'true' || data.is_active === true,
        } as PipelineFormData;
        onSubmit(payload);
    };

    return (
        <EntityForm<PipelineFormData>
            form={
                form as unknown as UseFormReturn<
                    PipelineFormData,
                    unknown,
                    PipelineFormData
                >
            }
            open={open}
            onOpenChange={onOpenChange}
            title={
                entity
                    ? fl('edit_title', 'Edit Pipeline')
                    : fl('add_title', 'Add New Pipeline')
            }
            onSubmit={handleSubmit}
            isLoading={isLoading}
        >
            {entity ? (
                <Tabs defaultValue="details">
                    <TabsList className="mb-4">
                        <TabsTrigger value="details">
                            {fl('tab_details', 'Details')}
                        </TabsTrigger>
                        <TabsTrigger value="states">
                            {fl('tab_states', 'States')}
                        </TabsTrigger>
                        <TabsTrigger value="transitions">
                            {fl('tab_transitions', 'Transitions')}
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="details" className="space-y-4">
                        {renderBasicInfoSection(fl)}
                        {renderDetailsSection(fl)}
                    </TabsContent>

                    <TabsContent value="states" className="space-y-4">
                        <PipelineStateManager pipelineId={entity.id} />
                    </TabsContent>

                    <TabsContent value="transitions" className="space-y-4">
                        <PipelineTransitionManager pipelineId={entity.id} />
                    </TabsContent>
                </Tabs>
            ) : (
                <div className="space-y-4">
                    {renderBasicInfoSection(fl)}
                    {renderDetailsSection(fl)}
                </div>
            )}
        </EntityForm>
    );
});
