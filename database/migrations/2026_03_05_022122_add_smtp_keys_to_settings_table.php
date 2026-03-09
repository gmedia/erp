<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            ['group' => 'smtp', 'key' => 'mail_host', 'value' => env('MAIL_HOST', '127.0.0.1'), 'type' => 'string'],
            ['group' => 'smtp', 'key' => 'mail_port', 'value' => env('MAIL_PORT', '2525'), 'type' => 'string'],
            ['group' => 'smtp', 'key' => 'mail_username', 'value' => env('MAIL_USERNAME', ''), 'type' => 'string'],
            ['group' => 'smtp', 'key' => 'mail_password', 'value' => env('MAIL_PASSWORD', ''), 'type' => 'string'],
            ['group' => 'smtp', 'key' => 'mail_encryption', 'value' => env('MAIL_ENCRYPTION', 'tls'), 'type' => 'string'],
            ['group' => 'smtp', 'key' => 'mail_from_address', 'value' => env('MAIL_FROM_ADDRESS', 'hello@example.com'), 'type' => 'string'],
            ['group' => 'smtp', 'key' => 'mail_from_name', 'value' => env('MAIL_FROM_NAME', config('app.name')), 'type' => 'string'],
        ];

        foreach ($settings as $setting) {
            \App\Models\Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    public function down(): void
    {
        $keys = [
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address',
            'mail_from_name',
        ];

        \App\Models\Setting::whereIn('key', $keys)->delete();
    }
};
