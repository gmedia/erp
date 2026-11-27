import {
    LabelText,
    type TranslatableLabel,
} from '@/components/common/TranslatedText';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/contexts/i18n-context';
import { createSortingHeader } from '@/utils/columns';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
import type { ColumnDef } from '@tanstack/react-table';

export interface AuditLogItem {
    id: number;
    event: string | null;
    description: string;
    log_name: string | null;
    subject_type: string | null;
    subject_id: number | null;
    causer_id: number | null;
    causer_name: string | null;
    attribute_changes: Record<string, unknown> | null;
    created_at: string;
}

const EVENT_LABELS: Record<string, string> = {
    created: 'Created',
    updated: 'Updated',
    deleted: 'Deleted',
};

const EVENT_BADGE_CLASS: Record<string, string> = {
    created: 'bg-success-soft text-success',
    updated: 'bg-warning-soft text-warning',
    deleted: 'bg-destructive-soft text-destructive',
};

function columnLabel(column: string, fallback: string): TranslatableLabel {
    return { key: `page.audit_logs.column_${column}`, fallback };
}

type Translate = (key: string, params?: Record<string, string>) => string;

function auditFieldLabel(t: Translate, field: string): string {
    const key = `page.audit_logs.field_${field}`;
    const translated = t(key);

    return translated === key ? field.replaceAll('_', ' ') : translated;
}

function auditSummary(t: Translate, item: AuditLogItem): string {
    const changes = item.attribute_changes ?? {};
    const oldValues = (changes.old ?? {}) as Record<string, unknown>;
    const newValues = (changes.attributes ?? {}) as Record<string, unknown>;
    const fields = Array.from(
        new Set([...Object.keys(oldValues), ...Object.keys(newValues)]),
    );

    if (item.event === 'created') {
        return t('page.audit_logs.summary_created');
    }

    if (item.event === 'deleted') {
        return t('page.audit_logs.summary_deleted');
    }

    if (item.event === 'updated') {
        if (fields.length === 0) {
            return t('page.audit_logs.summary_updated_empty');
        }

        return t('page.audit_logs.summary_updated', {
            count: String(fields.length),
            fields: fields.map((field) => auditFieldLabel(t, field)).join(', '),
        });
    }

    return t('page.audit_logs.summary_unknown');
}

function AuditUserCell({ name }: Readonly<{ name: string | null }>) {
    const { t } = useTranslation();

    return <div>{name ?? t('page.audit_logs.system_user')}</div>;
}

function AuditSummaryButton({
    item,
    onShowDetail,
}: Readonly<{
    item: AuditLogItem;
    onShowDetail: (item: AuditLogItem) => void;
}>) {
    const { t } = useTranslation();

    return (
        <Button
            type="button"
            variant="link"
            className="h-auto p-0 text-left"
            onClick={() => onShowDetail(item)}
        >
            {auditSummary(t, item)}
        </Button>
    );
}

export const createAuditLogColumns = (
    onShowDetail: (item: AuditLogItem) => void,
): ColumnDef<AuditLogItem>[] => [
    {
        accessorKey: 'created_at',
        ...createSortingHeader(columnLabel('date', 'Date')),
        cell: ({ row }) => (
            <div>
                {formatDateTimeByRegionalSettings(
                    row.getValue('created_at') as string,
                )}
            </div>
        ),
    },
    {
        accessorKey: 'causer_name',
        header: () => <LabelText label={columnLabel('user', 'User')} />,
        cell: ({ row }) => <AuditUserCell name={row.original.causer_name} />,
        enableSorting: false,
    },
    {
        accessorKey: 'subject_type',
        header: () => <LabelText label={columnLabel('module', 'Module')} />,
        cell: ({ row }) => <div>{row.original.subject_type ?? '-'}</div>,
        enableSorting: false,
    },
    {
        accessorKey: 'subject_id',
        header: () => (
            <LabelText label={columnLabel('document_id', 'Document ID')} />
        ),
        cell: ({ row }) => <div>{row.original.subject_id ?? '-'}</div>,
        enableSorting: false,
    },
    {
        accessorKey: 'event',
        ...createSortingHeader(columnLabel('action', 'Action')),
        cell: ({ row }) => {
            const event = (row.getValue('event') as string | null) ?? '';
            return (
                <Badge
                    variant="outline"
                    className={EVENT_BADGE_CLASS[event] ?? ''}
                >
                    <LabelText
                        label={{
                            key: `filter.audit_event_${event}`,
                            fallback: EVENT_LABELS[event] ?? event,
                        }}
                    />
                </Badge>
            );
        },
    },
    {
        accessorKey: 'description',
        header: () => <LabelText label={columnLabel('summary', 'Summary')} />,
        cell: ({ row }) => (
            <AuditSummaryButton
                item={row.original}
                onShowDetail={onShowDetail}
            />
        ),
        enableSorting: false,
    },
];
