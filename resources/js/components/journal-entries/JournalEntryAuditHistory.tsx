import type { AuditLogItem } from '@/components/audit-logs/Columns';
import { Badge } from '@/components/ui/badge';
import axios from '@/lib/axios';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
import { useQuery } from '@tanstack/react-query';
import { memo } from 'react';

interface JournalEntryAuditHistoryProps {
    journalEntryId: number;
}

interface AuditLogListResponse {
    data: AuditLogItem[];
}

function formatChangeValue(value: unknown): string {
    if (value === null || value === undefined) {
        return '-';
    }
    if (typeof value === 'object') {
        return JSON.stringify(value);
    }
    return String(value);
}

export const JournalEntryAuditHistory = memo(function JournalEntryAuditHistory({
    journalEntryId,
}: Readonly<JournalEntryAuditHistoryProps>) {
    const { data, isLoading } = useQuery({
        queryKey: ['audit-logs', 'journal-entry', journalEntryId],
        queryFn: async () => {
            const response = await axios.get<AuditLogListResponse>(
                '/api/audit-logs',
                {
                    params: {
                        subject_type: 'App\\Models\\JournalEntry',
                        subject_id: journalEntryId,
                        sort_by: 'created_at',
                        sort_direction: 'desc',
                        per_page: 20,
                    },
                },
            );
            return response.data.data;
        },
    });

    if (isLoading) {
        return <p className="text-sm text-muted-foreground">Memuat riwayat…</p>;
    }

    if (!data || data.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                Belum ada riwayat perubahan.
            </p>
        );
    }

    return (
        <ul className="space-y-3">
            {data.map((entry) => (
                <li key={entry.id} className="rounded-md border p-3">
                    <div className="flex flex-wrap items-center gap-2 text-sm">
                        <Badge variant="outline">{entry.event ?? '-'}</Badge>
                        <span className="font-medium">
                            {entry.causer_name ?? '-'}
                        </span>
                        <span className="text-muted-foreground">
                            {formatDateTimeByRegionalSettings(entry.created_at)}
                        </span>
                    </div>
                    {entry.attribute_changes?.old ? (
                        <div className="mt-2 grid gap-1 text-sm">
                            {Object.entries(
                                entry.attribute_changes.old as Record<
                                    string,
                                    unknown
                                >,
                            ).map(([field, oldValue]) => {
                                const newValue = (
                                    entry.attribute_changes?.attributes as
                                        | Record<string, unknown>
                                        | undefined
                                )?.[field];
                                return (
                                    <div key={field} className="break-all">
                                        <span className="font-medium">
                                            {field}
                                        </span>
                                        : {formatChangeValue(oldValue)} →{' '}
                                        {formatChangeValue(newValue)}
                                    </div>
                                );
                            })}
                        </div>
                    ) : null}
                </li>
            ))}
        </ul>
    );
});
