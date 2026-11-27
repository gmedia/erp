<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Menyalin isi disk object storage aplikasi ke folder staging lokal agar ikut diarsipkan
 * oleh spatie/laravel-backup (paket hanya membaca path lokal). Berkas yang ukuran dan
 * waktu ubahnya sama dengan salinan sebelumnya dilewati. Batas jumlah berkas dan total
 * byte diatur lewat env BACKUP_STAGING_MAX_FILES dan BACKUP_STAGING_MAX_BYTES.
 */
class StageObjectStorageForBackup extends Command
{
    protected $signature = 'backup:stage-object-storage {--clear : Hapus folder staging dan keluar}';

    protected $description = 'Salin object storage aplikasi ke folder staging lokal sebelum backup';

    public function handle(Filesystem $files): int
    {
        $diskName = (string) config('filesystems.default');
        $stagingRoot = (string) config('backup.staging.path');
        $maxFiles = (int) config('backup.staging.max_files');
        $maxBytes = (int) config('backup.staging.max_bytes');

        if ($this->option('clear')) {
            $files->deleteDirectory($stagingRoot);
            $this->info('Staging object storage dihapus.');

            return self::SUCCESS;
        }

        try {
            $disk = Storage::disk($diskName);
            $paths = $disk->allFiles();
        } catch (Throwable $exception) {
            $this->error('Disk sumber tidak dapat dibaca (' . class_basename($exception) . ').');

            return self::FAILURE;
        }

        $files->ensureDirectoryExists($stagingRoot);

        $copied = 0;
        $skipped = 0;
        $bytes = 0;

        foreach ($paths as $path) {
            if ($copied + $skipped >= $maxFiles || $bytes >= $maxBytes) {
                $this->warn('Batas staging tercapai; sisa berkas tidak dibackup.');

                break;
            }

            $target = $stagingRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

            if ($this->isUnchanged($disk, $path, $target)) {
                $skipped++;

                continue;
            }

            $files->ensureDirectoryExists(dirname($target));
            $stream = $disk->readStream($path);

            if (! is_resource($stream)) {
                continue;
            }

            $handle = fopen($target, 'wb');
            stream_copy_to_stream($stream, $handle);
            fclose($handle);
            fclose($stream);

            touch($target, (int) $disk->lastModified($path));
            $bytes += (int) filesize($target);
            $copied++;
        }

        $this->info("Staging object storage: {$copied} disalin, {$skipped} tidak berubah.");

        return self::SUCCESS;
    }

    private function isUnchanged($disk, string $path, string $target): bool
    {
        if (! is_file($target)) {
            return false;
        }

        return filesize($target) === $disk->size($path)
            && filemtime($target) === (int) $disk->lastModified($path);
    }
}
