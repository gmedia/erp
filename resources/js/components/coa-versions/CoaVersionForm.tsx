'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { useEffect, useState } from 'react';
import { useForm, type UseFormReturn } from 'react-hook-form';
import * as z from 'zod';

import EntityForm from '@/components/common/EntityForm';
import NameField from '@/components/common/NameField';
import SelectField from '@/components/common/SelectField';
import axios from '@/lib/axios';
import { type CoaVersion } from '@/types/coa-version';
import { coaVersionFormSchema, type CoaVersionFormData } from '@/utils/schemas';

interface CoaVersionFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    coaVersion?: CoaVersion | null;
    entity?: CoaVersion | null;
    onSubmit: (data: CoaVersionFormData) => void;
    isLoading?: boolean;
}

export function CoaVersionForm({
    open,
    onOpenChange,
    coaVersion,
    entity,
    onSubmit,
    isLoading = false,
}: CoaVersionFormProps) {
    const activeEntity = coaVersion || entity;
    const [fiscalYears, setFiscalYears] = useState<
        { label: string; value: string }[]
    >([]);

    const form = useForm<z.input<typeof coaVersionFormSchema>>({
        resolver: zodResolver(coaVersionFormSchema),
        defaultValues: {
            name: activeEntity?.name || '',
            fiscal_year_id: activeEntity?.fiscal_year_id || undefined,
            status: activeEntity?.status || 'draft',
        },
    });

    useEffect(() => {
        const fetchFiscalYears = async () => {
            try {
                const response = await axios.get(
                    '/api/fiscal-years?per_page=100',
                );
                const data = response.data.data.map(
                    (fy: { name: string; id: number }) => ({
                        label: fy.name,
                        value: fy.id.toString(),
                    }),
                );
                setFiscalYears(data);
            } catch (error) {
                console.error('Failed to fetch fiscal years', error);
            }
        };

        if (open) {
            fetchFiscalYears();
            form.reset({
                name: activeEntity?.name || '',
                fiscal_year_id: activeEntity?.fiscal_year_id || undefined,
                status: activeEntity?.status || 'draft',
            });
        }
    }, [form, activeEntity, open]);

    const handleFormSubmit = (data: CoaVersionFormData) => {
        onSubmit(data);
    };

    return (
        <EntityForm
            open={open}
            onOpenChange={onOpenChange}
            title={activeEntity ? 'Edit COA Version' : 'Create COA Version'}
            form={
                form as unknown as UseFormReturn<
                    CoaVersionFormData,
                    unknown,
                    CoaVersionFormData
                >
            }
            onSubmit={handleFormSubmit}
            isLoading={isLoading}
        >
            <NameField name="name" />

            <SelectField
                name="fiscal_year_id"
                label="Fiscal Year"
                placeholder="Select fiscal year"
                options={fiscalYears}
            />

            <SelectField
                name="status"
                label="Status"
                placeholder="Select status"
                options={[
                    { label: 'Draft', value: 'draft' },
                    { label: 'Active', value: 'active' },
                    { label: 'Archived', value: 'archived' },
                ]}
            />
        </EntityForm>
    );
}
