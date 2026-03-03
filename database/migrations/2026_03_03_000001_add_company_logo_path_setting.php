<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Setting::query()->updateOrCreate(
            ['key' => 'company_logo_path'],
            [
                'group' => 'general',
                'key' => 'company_logo_path',
                'value' => null,
                'type' => 'string',
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::query()->where('key', 'company_logo_path')->delete();
    }
};
