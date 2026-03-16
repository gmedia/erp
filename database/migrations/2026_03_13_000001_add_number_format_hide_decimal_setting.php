<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::query()->updateOrCreate(
            ['key' => 'number_format_hide_decimal'],
            [
                'group' => 'regional',
                'key' => 'number_format_hide_decimal',
                'value' => '0',
                'type' => 'boolean',
            ]
        );
    }

    public function down(): void
    {
        Setting::query()->where('key', 'number_format_hide_decimal')->delete();
    }
};
