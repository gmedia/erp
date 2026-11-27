<?php

namespace App\Exports;

use App\Actions\SecurityLogs\IndexSecurityLogAction;
use App\Exports\Concerns\AbstractActionCollectionExport;
use App\Http\Requests\SecurityLogs\IndexSecurityLogRequest;
use App\Models\SecurityLog;
use App\Support\LocaleJsonTranslator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SecurityLogExport extends AbstractActionCollectionExport implements WithHeadings, WithMapping
{
    /**
     * Header mengikuti locale default perusahaan (APP_LOCALE). Export lain belum berlocale,
     * jadi tidak ada locale per pengguna yang bisa diikuti.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        $locale = (string) config('app.locale', 'en');

        return array_map(
            fn (string $key): string => LocaleJsonTranslator::get("security_logs.export_headers.{$key}", [], $locale),
            ['date', 'event', 'user', 'attempted_email', 'ip_address', 'user_agent', 'metadata'],
        );
    }

    /**
     * @param  SecurityLog  $log
     */
    public function map($log): array
    {
        return [
            $log->created_at->format('Y-m-d H:i:s'),
            ucfirst(str_replace('_', ' ', $log->event)),
            $log->user->name ?? '-',
            $log->attempted_email ?? '-',
            $log->ip_address ?? '-',
            $log->user_agent ?? '-',
            is_array($log->metadata) ? json_encode($log->metadata) : '-',
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
        return IndexSecurityLogAction::class;
    }

    protected function requestClass(): string
    {
        return IndexSecurityLogRequest::class;
    }
}
