<?php

namespace App\Health\Checks;

use App\Health\SystemHealthMessage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Throwable;

/**
 * Memastikan object storage default bisa menulis, membaca, dan menghapus berkas kecil.
 * Pesan error tidak memuat endpoint, bucket, atau kredensial: hanya kelas exception.
 */
class ObjectStorageCheck extends Check
{
    private const PROBE_DIRECTORY = 'health-probes';

    private const HINT = 'Penyebab umum: AWS_SECRET_ACCESS_KEY kosong di .env '
        . 'atau bucket belum dibuat (bash scripts/minio-bucket.sh).';

    public function run(): Result
    {
        $disk = (string) config('filesystems.default');
        $path = self::PROBE_DIRECTORY . '/' . Str::uuid() . '.txt';
        $payload = 'health-' . Str::random(16);

        try {
            $storage = Storage::disk($disk);

            if (! $storage->put($path, $payload)) {
                return Result::make()
                    ->failed('Penyimpanan objek gagal menulis berkas uji. ' . self::HINT)
                    ->meta(['disk' => $disk] + SystemHealthMessage::meta('system_health.storage.write_failed_hint'));
            }

            if ($storage->get($path) !== $payload) {
                return Result::make()
                    ->failed('Penyimpanan objek membaca isi berkas uji yang berbeda.')
                    ->meta(['disk' => $disk] + SystemHealthMessage::meta('system_health.storage.content_mismatch'));
            }

            $storage->delete($path);
        } catch (Throwable $exception) {
            report($exception);

            return Result::make()
                ->failed('Penyimpanan objek tidak dapat diakses (' . class_basename($exception) . '). ' . self::HINT)
                ->meta(['disk' => $disk] + SystemHealthMessage::meta('system_health.storage.unreachable_hint', [
                    'exception' => class_basename($exception),
                ]));
        }

        return Result::make()
            ->ok('Penyimpanan objek dapat menulis, membaca, dan menghapus berkas.')
            ->meta(['disk' => $disk] + SystemHealthMessage::meta('system_health.storage.ok'))
            ->shortSummary('Lulus');
    }
}
