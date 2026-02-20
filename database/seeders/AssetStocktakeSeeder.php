<?php

namespace Database\Seeders;

use App\Models\AssetStocktake;
use Illuminate\Database\Seeder;

class AssetStocktakeSeeder extends Seeder
{
    public function run(): void
    {
        AssetStocktake::factory()->count(10)->create();
    }
}
