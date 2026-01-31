'use client';

import * as React from 'react';
import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';

import EntityForm from '@/components/common/EntityForm';
import NameField from '@/components/common/NameField';
import SelectField from '@/components/common/SelectField';
import { coaVersionFormSchema, type CoaVersionFormData } from '@/utils/schemas';
import { type CoaVersion } from '@/types/coa-version';
import axios from 'axios';

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
    const [fiscalYears, setFiscalYears] = useState<{ label: string; value: string }[]>([]);

    const form = useForm<CoaVersionFormData>({
        resolver: zodResolver(coaVersionFormSchema) as any,
        defaultValues: {
            name: activeEntity?.name || '',
            fiscal_year_id: activeEntity?.fiscal_year_id || undefined,
            status: activeEntity?.status || 'draft',
        },
    });

    useEffect(() => {
        const fetchFiscalYears = async () => {
            try {
                const response = await axios.get('/api/fiscal-years?per_page=100');
                const data = response.data.data.map((fy: any) => ({
                    label: fy.name,
                    value: fy.id.toString(),
                }));
                setFiscalYears(data);
            } catch (error) {
                console.error('Failed to fetch fiscal years', error);
            }
        };

        if (open) {
            fetchFiscalYears();
            form.reset({
                name: activeEntity?.name || '',
                fiscal_year_id: (activeEntity?.fiscal_year_id as any) || undefined,
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
            form={form}
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
