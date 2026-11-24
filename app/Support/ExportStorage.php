<?php

namespace App\Support;

use App\Exports\Concerns\TextSafeValueBinder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\DefaultValueBinder;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

/**
 * Resolves where generated files (Excel exports, asset-movement documents) are
 * written and how their download URL is produced.
 *
 * Production runs multiple replicas across nodes with no shared volume, so a
 * container-local disk loses the file on the next deploy and 404s whenever the
 * request lands on a different replica. Setting EXPORT_DISK=s3 moves the files
 * to object storage. The default stays "public" so local and test setups keep
 * working unchanged.
 */
final class ExportStorage
{
    public static function diskName(): string
    {
        return (string) config('filesystems.export_disk', 'public');
    }

    public static function disk(): FilesystemAdapter
    {
        return Storage::disk(self::diskName());
    }

    /**
     * Store a generated export on the configured disk.
     */
    public static function store(object $export, string $path, ?string $writerType = null): void
    {
        TextSafeValueBinder::neutralizeFormulaPrefixes($writerType === ExcelFormat::CSV);

        try {
            Excel::store($export, $path, self::diskName(), $writerType);
        } finally {
            TextSafeValueBinder::neutralizeFormulaPrefixes(false);
            self::restoreDefaultValueBinder();
        }
    }

    /**
     * Binder ekspor bersifat global (PhpSpreadsheet Cell). Pulihkan binder default
     * agar pembacaan (impor) sesudahnya tidak ikut memakai binder teks.
     */
    private static function restoreDefaultValueBinder(): void
    {
        Cell::setValueBinder(app(config('excel.value_binder.default', DefaultValueBinder::class)));
    }

    /**
     * Build a download URL for a stored file.
     *
     * Object storage is private, so a plain url() would return a link that
     * answers 403. Use a short-lived signed URL there and a static URL for
     * local/public disks.
     */
    public static function url(string $path): string
    {
        $disk = self::disk();

        if (self::driver() === 's3') {
            return $disk->temporaryUrl(
                $path,
                now()->addMinutes((int) config('filesystems.export_url_ttl_minutes', 30)),
            );
        }

        return $disk->url($path);
    }

    private static function driver(): string
    {
        return (string) config('filesystems.disks.'.self::diskName().'.driver', 'local');
    }
}
