<?php

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetModel;
use App\Models\Branch;
use App\Models\AssetLocation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Supplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('assets');

beforeEach(function () {
    // Create user with necessary permissions
    $this->user = createTestUserWithPermissions(['asset', 'asset.create']);
    actingAs($this->user);

    $this->category = AssetCategory::factory()->create(['name' => 'IT Equipment']);
    $this->model = AssetModel::factory()->create(['model_name' => 'MacBook Pro', 'asset_category_id' => $this->category->id]);
    $this->branch = Branch::factory()->create(['name' => 'Head Office']);
    $this->location = AssetLocation::factory()->create(['name' => 'Room 101', 'branch_id' => $this->branch->id]);
    $this->department = Department::factory()->create(['name' => 'IT Support']);
    $this->employee = Employee::factory()->create(['name' => 'John Doe']);
    $this->supplier = Supplier::factory()->create(['name' => 'Apple Store']);
});

test('can import assets from csv file', function () {
    // Header + 1 row
    $csvContent = implode("\n", [
        'asset_code,name,asset_category,asset_model,branch,location,department,employee,supplier,serial_number,barcode,purchase_date,purchase_cost,currency,warranty_end_date,status,condition,notes',
        'AST-001,Laptop Mac,IT Equipment,MacBook Pro,Head Office,Room 101,IT Support,John Doe,Apple Store,SN12345,BC123,2023-01-01,15000000,IDR,2024-01-01,active,good,Good condition',
    ]);
    
    $file = UploadedFile::fake()->createWithContent('assets.csv', $csvContent);

    $response = $this->postJson('/api/assets/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'imported' => 1,
            'skipped' => 0,
            'errors' => [],
        ]);

    $this->assertDatabaseHas('assets', [
        'asset_code' => 'AST-001',
        'name' => 'Laptop Mac',
        'asset_category_id' => $this->category->id,
        'asset_model_id' => $this->model->id,
        'branch_id' => $this->branch->id,
        'status' => 'active',
        'condition' => 'good',
    ]);
})->group('assets');

test('returns validation errors for invalid rows', function () {
    $csvContent = implode("\n", [
        'asset_code,name,asset_category,asset_model,branch,location,department,employee,supplier,serial_number,barcode,purchase_date,purchase_cost,currency,warranty_end_date,status,condition,notes',
        'AST-002,Laptop Mac,IT Equipment,MacBook Pro,Head Office,Room 101,IT Support,John Doe,Apple Store,SN12345,BC123,2023-01-01,15000000,IDR,2024-01-01,active,good,Good condition',
        ',No Code,IT Equipment,MacBook Pro,Head Office,Room 101,IT Support,John Doe,Apple Store,SN12345,BC123,2023-01-01,15000000,IDR,2024-01-01,active,good,Good condition',
        'AST-004,Invalid Status,IT Equipment,MacBook Pro,Head Office,Room 101,IT Support,John Doe,Apple Store,SN12345,BC123,2023-01-01,15000000,IDR,2024-01-01,invalid_status,good,Good condition',
    ]);
    
    $file = UploadedFile::fake()->createWithContent('assets_invalid.csv', $csvContent);

    $response = $this->postJson('/api/assets/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'imported' => 1,
            'skipped' => 2,
        ]);
        
    $this->assertCount(2, $response->json('errors'));
    
    $errors = collect($response->json('errors'));
    $this->assertTrue($errors->contains(fn ($e) => $e['row'] == 3 && $e['field'] == 'Validation'));
    $this->assertTrue($errors->contains(fn ($e) => $e['row'] == 4 && $e['field'] == 'Validation'));
})->group('assets');

test('returns errors for unknown foreign keys', function () {
    $csvContent = implode("\n", [
        'asset_code,name,asset_category,asset_model,branch,location,department,employee,supplier,serial_number,barcode,purchase_date,purchase_cost,currency,warranty_end_date,status,condition,notes',
        'AST-005,Laptop Mac,Unknown Category,MacBook Pro,Head Office,Room 101,IT Support,John Doe,Apple Store,SN12345,BC123,2023-01-01,15000000,IDR,2024-01-01,active,good,Good condition',
    ]);
    
    $file = UploadedFile::fake()->createWithContent('assets_fk.csv', $csvContent);

    $response = $this->postJson('/api/assets/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200);
    $errors = collect($response->json('errors'));
    
    $this->assertTrue($errors->contains(fn ($e) => $e['field'] == 'asset_category' && str_contains($e['message'], 'not found')));
})->group('assets');

test('upserts existing asset by asset code', function () {
    Asset::factory()->create([
        'asset_code' => 'AST-EXIST',
        'name' => 'Old Name',
        'status' => 'draft'
    ]);

    $csvContent = implode("\n", [
        'asset_code,name,asset_category,asset_model,branch,location,department,employee,supplier,serial_number,barcode,purchase_date,purchase_cost,currency,warranty_end_date,status,condition,notes',
        'AST-EXIST,New Name,IT Equipment,MacBook Pro,Head Office,Room 101,IT Support,John Doe,Apple Store,SN12345,BC123,2023-01-01,15000000,IDR,2024-01-01,active,good,Good condition',
    ]);
    
    $file = UploadedFile::fake()->createWithContent('assets_upsert.csv', $csvContent);

    $response = $this->postJson('/api/assets/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'imported' => 1,
            'errors' => [],
        ]);

    $this->assertDatabaseHas('assets', [
        'asset_code' => 'AST-EXIST',
        'name' => 'New Name',
        'status' => 'active',
    ]);
})->group('assets');

test('validates file type', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100);

    $response = $this->postJson('/api/assets/import', [
        'file' => $file,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
})->group('assets');
