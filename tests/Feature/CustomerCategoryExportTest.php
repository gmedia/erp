<?php

namespace Tests\Feature;

use App\Models\CustomerCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerCategoryExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_generates_excel_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        CustomerCategory::factory()->count(5)->create();

        $response = $this->actingAs($user)->post(route('customer-categories.export'));

        $response->assertOk()
            ->assertJsonStructure(['url', 'filename']);

        $filename = $response->json('filename');
        Storage::disk('public')->assertExists('exports/' . $filename);
    }
}
