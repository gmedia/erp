<?php

namespace App\Actions\SystemHealth;

use App\Health\SystemHealthSanitizer;
use App\Health\SystemHealthThresholds;
use Spatie\Health\Models\HealthCheckResultHistoryItem;
use Spatie\Health\ResultStores\EloquentHealthResultStore;

class GetSystemHealthStatusAction
{
    private const HISTORY_LIMIT = 20;

    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        $latest = (new EloquentHealthResultStore)->latestResults();

        $components = $latest === null
            ? []
            : $latest->storedCheckResults->map(fn ($stored): array => [
                'name' => $stored->name,
                'label' => $stored->label,
                'status' => (string) $stored->status,
                'summary' => $stored->shortSummary,
                'message' => SystemHealthSanitizer::message($stored->notificationMessage),
                'meta' => SystemHealthSanitizer::meta((array) $stored->meta),
            ])->values()->all();

        $history = HealthCheckResultHistoryItem::query()
            ->where('status', '!=', 'ok')
            ->where('status', '!=', 'skipped')
            ->latest('created_at')
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->map(fn (HealthCheckResultHistoryItem $item): array => [
                'check' => $item->check_label,
                'status' => $item->status,
                'message' => SystemHealthSanitizer::message($item->notification_message),
                'checked_at' => $item->created_at->toIso8601String(),
            ])
            ->values()
            ->all();

        return [
            'checked_at' => $latest?->finishedAt->format(DATE_ATOM),
            'overall' => $this->overall($components),
            'components' => $components,
            'history' => $history,
            'thresholds' => $this->thresholds(),
            'version' => [
                'app' => (string) config('app.version', 'dev'),
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $components
     */
    private function overall(array $components): string
    {
        $statuses = array_column($components, 'status');

        if ($statuses === []) {
            return 'unknown';
        }

        if (array_intersect($statuses, ['failed', 'crashed']) !== []) {
            return 'failed';
        }

        return in_array('warning', $statuses, true) ? 'warning' : 'ok';
    }

    /**
     * @return array<string, int>
     */
    private function thresholds(): array
    {
        $values = [];

        foreach (array_keys(SystemHealthThresholds::definitions()) as $key) {
            $values[$key] = SystemHealthThresholds::get($key);
        }

        return $values;
    }
}
