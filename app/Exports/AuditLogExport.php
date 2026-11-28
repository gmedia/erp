<?php

namespace App\Exports;

use App\Actions\AuditLog\IndexAuditLogAction;
use App\Exports\Concerns\AbstractActionCollectionExport;
use App\Http\Requests\AuditLogs\IndexAuditLogRequest;
use App\Models\User;
use App\Support\LocaleJsonTranslator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Spatie\Activitylog\Models\Activity;

class AuditLogExport extends AbstractActionCollectionExport implements WithHeadings, WithMapping
{
    /**
     * Header mengikuti locale default perusahaan (APP_LOCALE).
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        $locale = (string) config('app.locale', 'en');

        return array_map(
            fn (string $key): string => LocaleJsonTranslator::get("audit_logs.export_headers.{$key}", [], $locale),
            ['date', 'event', 'user', 'module', 'document_id', 'summary', 'changes'],
        );
    }

    /**
     * @param  Activity  $log
     */
    public function map($log): array
    {
        return [
            $log->created_at->format('Y-m-d H:i:s'),
            $log->event ?? '-',
            $log->causer instanceof User ? $log->causer->name : '-',
            $log->subject_type ?? '-',
            $log->subject_id ?? '-',
            $log->description,
            $log->attribute_changes ? json_encode($log->attribute_changes) : '-',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function prepareFilters(array $filters): array
    {
        $filters['export'] = true;

        return $filters;
    }

    protected function actionClass(): string
    {
        return IndexAuditLogAction::class;
    }

    protected function requestClass(): string
    {
        return IndexAuditLogRequest::class;
    }
}
