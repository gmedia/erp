<?php

namespace App\Health;

use App\Models\Permission;
use App\Models\User;
use App\Notifiables\SystemHealthBackupFailedEvent;
use App\Notifications\GenericAppNotification;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Backup\Events\BackupHasFailed;
use Throwable;

/**
 * Mengirim notifikasi ketika backup terjadwal gagal: in-app ke pengguna ber-permission
 * system_health dan email ke alamat admin IT dari Pengaturan. Pesan tidak menyertakan
 * path, host, atau kredensial: hanya nama kelas exception.
 */
class SystemHealthBackupFailureNotifier
{
    public function __construct(private readonly NotificationService $notifications)
    {
        // Intentionally empty. Dependencies are assigned via promoted properties.
    }

    public function notify(BackupHasFailed $event): void
    {
        $exceptionName = class_basename($event->exception);
        $payload = [
            'title' => 'Backup gagal',
            'body' => 'Backup database terjadwal gagal (' . $exceptionName . '). Periksa Kesehatan Sistem.',
            'check' => 'backup',
            'to' => 'failed',
        ];

        try {
            $this->notifications->send(new SystemHealthBackupFailedEvent(
                failureId: (string) Str::uuid(),
                audience: $this->audience(),
                payload: $payload,
            ));

            $email = SystemHealthThresholds::alertEmail();

            if ($email !== null) {
                Mail::send(new GenericAppNotification(
                    eventKey: 'system_health.backup.failed',
                    payload: $payload,
                    targetUrl: '/system-health',
                    recipientEmail: $email,
                ));
            }
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Backup failure notification could not be sent.');
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function audience(): Collection
    {
        $permission = Permission::query()->where('name', 'system_health')->first();

        if ($permission === null) {
            return collect();
        }

        return User::query()
            ->whereHas('employee.permissions', fn ($query) => $query->whereKey($permission->id))
            ->get();
    }
}
