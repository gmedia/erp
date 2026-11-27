<?php

namespace App\Actions\SystemHealth;

use App\Health\SystemHealthMessage;
use App\Health\SystemHealthSanitizer;
use App\Health\SystemHealthThresholds;
use Spatie\Health\Models\HealthCheckResultHistoryItem;
use Spatie\Health\ResultStores\EloquentHealthResultStore;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;

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
            : $latest->storedCheckResults
                ->map(fn (StoredCheckResult $stored): array => $this->component($stored))
                ->values()
                ->all();

        $history = HealthCheckResultHistoryItem::query()
            ->where('status', '!=', 'ok')
            ->where('status', '!=', 'skipped')
            ->latest('created_at')
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->map(function (HealthCheckResultHistoryItem $item): array {
                $resolved = SystemHealthMessage::fromPackage((string) $item->notification_message);

                return [
                    'check' => $item->check_label,
                    'status' => $item->status,
                    'message' => SystemHealthSanitizer::message($item->notification_message),
                    'message_code' => $resolved['code'],
                    'message_params' => $resolved['params'],
                    'checked_at' => $item->created_at->toIso8601String(),
                ];
            })
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
     * @return array<string, mixed>
     */
    private function component(StoredCheckResult $stored): array
    {
        $meta = (array) $stored->meta;
        $code = $meta['message_code'] ?? null;
        $params = [];

        if (! is_string($code)) {
            $resolved = SystemHealthMessage::fromPackage((string) $stored->notificationMessage, $meta);
            $code = $resolved['code'];
            $params = $resolved['params'];
        }

        return [
            'name' => $stored->name,
            'label' => $stored->label,
            'status' => (string) $stored->status,
            'summary' => $this->summary($stored->shortSummary),
            'message' => SystemHealthSanitizer::message($stored->notificationMessage),
            'message_code' => $code,
            'message_params' => $params,
            'meta' => SystemHealthSanitizer::meta($meta),
        ];
    }

    /**
     * Ringkasan paket yang hanya kata status Inggris (Failed/Ok/…) dibuang; UI memakai label status terjemahan.
     */
    private function summary(?string $summary): ?string
    {
        if ($summary === null || $summary === '') {
            return null;
        }

        return in_array(strtolower($summary), ['failed', 'ok', 'warning', 'crashed', 'skipped'], true)
            ? null
            : $summary;
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
