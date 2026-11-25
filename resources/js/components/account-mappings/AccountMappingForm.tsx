'use client';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { useTranslation } from '@/contexts/i18n-context';
import { type Account } from '@/types/account';
import { type AccountMapping } from '@/types/account-mapping';
import { type CoaVersion } from '@/types/coa-version';
import {
    accountMappingFormSchema,
    type AccountMappingFormData,
} from '@/utils/schemas';
import { zodResolver } from '@hookform/resolvers/zod';
import { useEffect, useMemo, useRef } from 'react';
import { useForm } from 'react-hook-form';
import * as z from 'zod';

interface AccountMappingFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    accountMapping?: AccountMapping | null;
    entity?: AccountMapping | null;
    item?: AccountMapping | null;
    onSubmit: (data: AccountMappingFormData) => void;
    isLoading?: boolean;
}

const coaVersionLabel = (v: CoaVersion) =>
    v?.status ? `${v.name} (${v.status})` : v?.name;

const accountLabel = (a: Account) =>
    a?.code ? `${a.code} - ${a.name}` : a?.name;

export function AccountMappingForm({
    open,
    onOpenChange,
    accountMapping,
    entity,
    item,
    onSubmit,
    isLoading = false,
}: Readonly<AccountMappingFormProps>) {
    const activeEntity = accountMapping || entity || item;
    const { t } = useTranslation();
    const fl = (key: string, fallback: string) => {
        const translated = t(`form_fields.account_mappings.${key}`);
        return translated === `form_fields.account_mappings.${key}`
            ? fallback
            : translated;
    };

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

    const form = useForm<z.input<typeof accountMappingFormSchema>>({
        resolver: zodResolver(accountMappingFormSchema),
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

    // Sumber boleh akun kelompok (saldo lama); target wajib akun daun.
    const sourceAccountsUrl = sourceCoaVersionId
        ? `/api/accounts?coa_version_id=${encodeURIComponent(sourceCoaVersionId)}`
        : '/api/accounts';

    const targetAccountsUrl = targetCoaVersionId
        ? `/api/accounts?coa_version_id=${encodeURIComponent(targetCoaVersionId)}&has_children=0`
        : '/api/accounts?has_children=0';

    return (
        <EntityForm<AccountMappingFormData>
            open={open}
            onOpenChange={onOpenChange}
            title={
                activeEntity
                    ? fl('edit_title', 'Edit Account Mapping')
                    : fl('create_title', 'Create Account Mapping')
            }
            form={form}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            <AsyncSelectField<CoaVersion>
                name="source_coa_version_id"
                label={fl('source_coa_version', 'Source COA Version')}
                url="/api/coa-versions"
                placeholder={fl(
                    'source_coa_version_placeholder',
                    'Select source COA version',
                )}
                labelFn={coaVersionLabel}
            />

            <AsyncSelectField<Account>
                name="source_account_id"
                label={fl('source_account', 'Source Account')}
                url={sourceAccountsUrl}
                placeholder={fl(
                    'source_account_placeholder',
                    'Select source account',
                )}
                labelFn={accountLabel}
            />

            <AsyncSelectField<CoaVersion>
                name="target_coa_version_id"
                label={fl('target_coa_version', 'Target COA Version')}
                url="/api/coa-versions"
                placeholder={fl(
                    'target_coa_version_placeholder',
                    'Select target COA version',
                )}
                labelFn={coaVersionLabel}
            />

            <AsyncSelectField<Account>
                name="target_account_id"
                label={fl('target_account', 'Target Account')}
                url={targetAccountsUrl}
                placeholder={fl(
                    'target_account_placeholder',
                    'Select target account',
                )}
                labelFn={accountLabel}
            />

            <SelectField
                name="type"
                label={fl('type', 'Type')}
                placeholder={fl('type_placeholder', 'Select type')}
                options={[
                    { label: fl('type_rename', 'Rename'), value: 'rename' },
                    { label: fl('type_merge', 'Merge'), value: 'merge' },
                    { label: fl('type_split', 'Split'), value: 'split' },
                ]}
            />

            <TextareaField
                name="notes"
                label={fl('notes', 'Notes')}
                placeholder={fl('notes_placeholder', 'Optional notes')}
                rows={3}
            />
        </EntityForm>
    );
}
