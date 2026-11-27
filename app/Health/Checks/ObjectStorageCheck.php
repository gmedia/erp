<?php

namespace App\Health\Checks;

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

    public function run(): Result
    {
        $disk = (string) config('filesystems.default');
        $path = self::PROBE_DIRECTORY . '/' . Str::uuid() . '.txt';
        $payload = 'health-' . Str::random(16);

        try {
            $storage = Storage::disk($disk);

            if (! $storage->put($path, $payload)) {
                return Result::make()->failed('Object storage gagal menulis berkas uji.')->meta(['disk' => $disk]);
            }

            if ($storage->get($path) !== $payload) {
                return Result::make()->failed('Object storage membaca isi berkas uji yang berbeda.')->meta(['disk' => $disk]);
            }

            $storage->delete($path);
        } catch (Throwable $exception) {
            report($exception);

            return Result::make()
                ->failed('Object storage tidak dapat diakses (' . class_basename($exception) . ').')
                ->meta(['disk' => $disk]);
        }

        return Result::make()
            ->ok('Object storage dapat menulis, membaca, dan menghapus berkas.')
            ->meta(['disk' => $disk])
            ->shortSummary('Lulus');
    }
}
