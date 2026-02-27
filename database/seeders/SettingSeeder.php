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
                'value' => 'Dokfin',
                'type' => 'string',
            ],
            [
                'group' => 'general',
                'key' => 'company_address',
                'value' => 'Jl. Siliwangi No.32G, Nogotirto, Kec. Gamping, Kab. Sleman, Daerah Istimewa Yogyakarta, 55592',
                'type' => 'string',
            ],
            [
                'group' => 'general',
                'key' => 'company_phone',
                'value' => '+62 274 380 345',
                'type' => 'string',
            ],
            [
                'group' => 'general',
                'key' => 'company_email',
                'value' => 'mail@dokfin.com',
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
