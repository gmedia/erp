<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General settings
            [
                'group' => 'general',
                'key' => 'company_name',
                'value' => '',
                'type' => 'string',
            ],
            [
                'group' => 'general',
                'key' => 'company_address',
                'value' => '',
                'type' => 'string',
            ],
            [
                'group' => 'general',
                'key' => 'company_phone',
                'value' => '',
                'type' => 'string',
            ],
            [
                'group' => 'general',
                'key' => 'company_email',
                'value' => '',
                'type' => 'string',
            ],

            // Regional settings
            [
                'group' => 'regional',
                'key' => 'timezone',
                'value' => 'Asia/Jakarta',
                'type' => 'string',
            ],
            [
                'group' => 'regional',
                'key' => 'currency',
                'value' => 'IDR',
                'type' => 'string',
            ],
            [
                'group' => 'regional',
                'key' => 'date_format',
                'value' => 'd/m/Y',
                'type' => 'string',
            ],
            [
                'group' => 'regional',
                'key' => 'number_format_decimal',
                'value' => ',',
                'type' => 'string',
            ],
            [
                'group' => 'regional',
                'key' => 'number_format_thousand',
                'value' => '.',
                'type' => 'string',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
