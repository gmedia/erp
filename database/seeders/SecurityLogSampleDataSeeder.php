<?php

namespace Database\Seeders;

use App\Models\SecurityLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SecurityLogSampleDataSeeder extends Seeder
{
    /**
     * Penanda di metadata untuk membedakan baris demo dari log produksi.
     * Seeder menghapus dan membuat ulang baris ber-penanda ini sehingga idempoten.
     */
    private const DEMO_KEY = 'demo_seed_577';

    private const UA_CHROME_WINDOWS = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

    private const UA_CHROME_MAC = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

    private const UA_SAFARI_IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.1 Mobile/15E148 Safari/604.1';

    private const UA_EDGE_WINDOWS = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0';

    public function run(): void
    {
        SecurityLog::query()
            ->where('metadata->' . self::DEMO_KEY, true)
            ->delete();

        $admin = User::where('email', config('app.admin'))->first();
        $rina = User::where('email', 'rina.kartika@example.com')->first();
        $hendra = User::where('email', 'manager.hr@example.com')->first();
        $agus = User::where('email', 'staff.it@example.com')->first();

        $now = Carbon::now();

        $employeeIds = DB::table('employees')
            ->whereNotNull('user_id')
            ->pluck('id', 'user_id')
            ->all();

        // [menit sebelum sekarang, event, user, email yang dicoba, ip, user agent, metadata]
        $rows = [
            [4320, SecurityLog::EVENT_LOGIN_SUCCESS, $rina, null, '192.168.10.21', self::UA_CHROME_WINDOWS, ['guard' => 'web']],
            [4290, SecurityLog::EVENT_LOGOUT, $rina, null, '192.168.10.21', self::UA_CHROME_WINDOWS, ['guard' => 'web']],
            [1440, SecurityLog::EVENT_LOGIN_FAILED, null, 'rina.kartika@example.com', '192.168.10.45', self::UA_SAFARI_IPHONE, ['guard' => 'web']],
            [1436, SecurityLog::EVENT_LOGIN_FAILED, null, 'rina.kartika@example.com', '192.168.10.45', self::UA_SAFARI_IPHONE, ['guard' => 'web']],
            [1432, SecurityLog::EVENT_LOGIN_SUCCESS, $rina, null, '192.168.10.45', self::UA_SAFARI_IPHONE, ['guard' => 'web']],
            [960, SecurityLog::EVENT_LOGIN_SUCCESS, $hendra, null, '192.168.20.7', self::UA_EDGE_WINDOWS, ['guard' => 'web', 'method' => 'social', 'provider' => 'google']],
            [955, SecurityLog::EVENT_LOGOUT, $hendra, null, '192.168.20.7', self::UA_EDGE_WINDOWS, ['guard' => 'web']],
            [310, SecurityLog::EVENT_LOGIN_FAILED, null, 'agus.prasetyo@example.com', '10.0.5.18', self::UA_CHROME_MAC, ['guard' => 'web']],
            [306, SecurityLog::EVENT_LOGIN_FAILED, null, 'agus.prasetyo@example.com', '10.0.5.18', self::UA_CHROME_MAC, ['guard' => 'web']],
            [302, SecurityLog::EVENT_LOGIN_FAILED, null, 'agus.prasetyo@example.com', '10.0.5.18', self::UA_CHROME_MAC, ['guard' => 'web']],
            [298, SecurityLog::EVENT_LOGIN_SUCCESS, $agus, null, '10.0.5.18', self::UA_CHROME_MAC, ['guard' => 'web']],
            [180, SecurityLog::EVENT_PASSWORD_RESET, $agus, null, '10.0.5.18', self::UA_CHROME_MAC, ['guard' => 'web']],
            [95, SecurityLog::EVENT_ROLE_CHANGED, $agus, null, '192.168.10.2', self::UA_CHROME_WINDOWS, ['employee_id' => $employeeIds[$agus?->id] ?? null, 'added' => ['approval_manage'], 'removed' => []]],
            [45, SecurityLog::EVENT_USER_CREATED, $hendra, null, '192.168.10.2', self::UA_CHROME_WINDOWS, ['employee_id' => $employeeIds[$hendra?->id] ?? null]],
            [12, SecurityLog::EVENT_LOGIN_SUCCESS, $admin, null, '192.168.10.2', self::UA_CHROME_WINDOWS, ['guard' => 'web']],
        ];

        foreach ($rows as [$minutesAgo, $event, $user, $attemptedEmail, $ip, $userAgent, $metadata]) {
            $createdAt = $now->copy()->subMinutes($minutesAgo);

            $log = new SecurityLog([
                'event' => $event,
                'user_id' => $user?->id,
                'attempted_email' => $attemptedEmail,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'metadata' => array_merge($metadata, [self::DEMO_KEY => true]),
            ]);
            $log->created_at = $createdAt;
            $log->save();
        }
    }
}
