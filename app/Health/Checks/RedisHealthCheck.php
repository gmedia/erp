<?php

namespace App\Health\Checks;

use App\Health\SystemHealthMessage;
use Illuminate\Support\Facades\Redis;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Throwable;

/**
 * Valkey/Redis: ping dan memori terpakai. Pesan error tidak menyertakan host atau kredensial.
 */
class RedisHealthCheck extends Check
{
    protected string $connectionName = 'default';

    public function connectionName(string $connectionName): self
    {
        $this->connectionName = $connectionName;

        return $this;
    }

    public function run(): Result
    {
        try {
            $connection = Redis::connection($this->connectionName);
            $pong = $connection->ping();
            $info = $connection->command('info', ['memory']);
        } catch (Throwable $exception) {
            report($exception);

            return Result::make()
                ->failed('Valkey/Redis tidak dapat diakses (' . class_basename($exception) . ').')
                ->meta(SystemHealthMessage::meta('system_health.redis.unreachable', [
                    'exception' => class_basename($exception),
                ]));
        }

        $alive = $pong === true || (is_string($pong) && str_contains(strtoupper($pong), 'PONG'));

        if (! $alive) {
            return Result::make()
                ->failed('Valkey/Redis tidak merespons ping.')
                ->meta(SystemHealthMessage::meta('system_health.redis.no_ping'));
        }

        $usedMemory = $this->usedMemoryBytes($info);

        return Result::make()
            ->meta([
                'used_memory_mb' => $usedMemory === null ? null : round($usedMemory / 1048576, 2),
            ] + SystemHealthMessage::meta('system_health.redis.ok'))
            ->shortSummary($usedMemory === null ? 'Lulus' : round($usedMemory / 1048576, 1) . ' MB')
            ->ok('Valkey/Redis merespons ping.');
    }

    /**
     * @param  mixed  $info
     */
    private function usedMemoryBytes($info): ?int
    {
        if (is_array($info)) {
            $value = $info['used_memory'] ?? null;

            return is_numeric($value) ? (int) $value : null;
        }

        if (is_string($info) && preg_match('/used_memory:(\d+)/', $info, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }
}
