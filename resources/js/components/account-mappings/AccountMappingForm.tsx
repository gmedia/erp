'use client';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { type AccountMapping } from '@/types/account-mapping';
import {
    accountMappingFormSchema,
    type AccountMappingFormData,
} from '@/utils/schemas';
import { zodResolver } from '@hookform/resolvers/zod';
import React, { useEffect, useMemo, useRef } from 'react';
import { useForm } from 'react-hook-form';

interface AccountMappingFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    accountMapping?: AccountMapping | null;
    entity?: AccountMapping | null;
    item?: AccountMapping | null;
    onSubmit: (data: AccountMappingFormData) => void;
    isLoading?: boolean;
}

const coaVersionLabel = (v: any) =>
    v?.status ? `${v.name} (${v.status})` : v?.name;

const accountLabel = (a: any) => (a?.code ? `${a.code} - ${a.name}` : a?.name);

export function AccountMappingForm({
    open,
    onOpenChange,
    accountMapping,
    entity,
    item,
    onSubmit,
    isLoading = false,
}: AccountMappingFormProps) {
    const activeEntity = accountMapping || entity || item;

    const defaultValues = useMemo<AccountMappingFormData>(
        () => ({
            source_coa_version_id: activeEntity?.source_account?.coa_version_id
                ? String(activeEntity.source_account.coa_version_id)
                : '',
            target_coa_version_id: activeEntity?.target_account?.coa_version_id
                ? String(activeEntity.target_account.coa_version_id)
                : '',
            source_account_id: activeEntity?.source_account_id
                ? String(activeEntity.source_account_id)
                : '',
            target_account_id: activeEntity?.target_account_id
                ? String(activeEntity.target_account_id)
                : '',
            type: activeEntity?.type || 'rename',
            notes: activeEntity?.notes || '',
        }),
        [activeEntity],
    );

    const form = useForm<AccountMappingFormData>({
        resolver: zodResolver(accountMappingFormSchema) as any,
        defaultValues,
    });

    useEffect(() => {
        if (open) {
            form.reset(defaultValues);
        }
    }, [open, form, defaultValues]);

    const sourceCoaVersionId = form.watch('source_coa_version_id');
    const targetCoaVersionId = form.watch('target_coa_version_id');

    const prevSourceCoaVersionId = useRef<string | undefined>(undefined);
    const prevTargetCoaVersionId = useRef<string | undefined>(undefined);

    useEffect(() => {
        if (!open) return;

        if (
            prevSourceCoaVersionId.current !== undefined &&
            prevSourceCoaVersionId.current !== sourceCoaVersionId
        ) {
            form.setValue('source_account_id', '');
        }

        prevSourceCoaVersionId.current = sourceCoaVersionId;
    }, [open, sourceCoaVersionId, form]);

    useEffect(() => {
        if (!open) return;

        if (
            prevTargetCoaVersionId.current !== undefined &&
            prevTargetCoaVersionId.current !== targetCoaVersionId
        ) {
            form.setValue('target_account_id', '');
        }

        prevTargetCoaVersionId.current = targetCoaVersionId;
    }, [open, targetCoaVersionId, form]);

    const sourceAccountsUrl = sourceCoaVersionId
        ? `/api/accounts?coa_version_id=${encodeURIComponent(sourceCoaVersionId)}`
        : '/api/accounts';

    const targetAccountsUrl = targetCoaVersionId
        ? `/api/accounts?coa_version_id=${encodeURIComponent(targetCoaVersionId)}`
        : '/api/accounts';

    return (
        <EntityForm<AccountMappingFormData>
            open={open}
            onOpenChange={onOpenChange}
            title={activeEntity ? 'Edit Account Mapping' : 'Create Account Mapping'}
            form={form}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            <AsyncSelectField
                name="source_coa_version_id"
                label="Source COA Version"
                url="/api/coa-versions"
                placeholder="Select source COA version"
                labelFn={coaVersionLabel}
            />

            <AsyncSelectField
                name="source_account_id"
                label="Source Account"
                url={sourceAccountsUrl}
                placeholder="Select source account"
                labelFn={accountLabel}
            />

            <AsyncSelectField
                name="target_coa_version_id"
                label="Target COA Version"
                url="/api/coa-versions"
                placeholder="Select target COA version"
                labelFn={coaVersionLabel}
            />

            <AsyncSelectField
                name="target_account_id"
                label="Target Account"
                url={targetAccountsUrl}
                placeholder="Select target account"
                labelFn={accountLabel}
            />

            <SelectField
                name="type"
                label="Type"
                placeholder="Select type"
                options={[
                    { label: 'Rename', value: 'rename' },
                    { label: 'Merge', value: 'merge' },
                    { label: 'Split', value: 'split' },
                ]}
            />

            <TextareaField
                name="notes"
                label="Notes"
                placeholder="Optional notes"
                rows={3}
            />
        </EntityForm>
    );
}
