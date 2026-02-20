<?php

use App\Models\Branch;
use App\Models\Supplier;
use App\Models\SupplierCategory;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    // Create user with necessary permissions
    $this->user = createTestUserWithPermissions(['supplier', 'supplier.create']);
    $this->actingAs($this->user);

    $this->category = SupplierCategory::factory()->create(['name' => 'IT Equipment']);
    $this->branch = Branch::factory()->create(['name' => 'Head Office']);
});

test('can import suppliers from csv file', function () {
    // Header + 1 row
    $csvContent = implode("\n", [
        'name,email,phone,address,branch,category,status',
        'Tech Supplier,tech@example.com,08123456789,123 Main St,Head Office,IT Equipment,active',
    ]);
    
    $file = UploadedFile::fake()->createWithContent('suppliers.csv', $csvContent);

    $response = $this->postJson('/api/suppliers/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'imported' => 1,
            'skipped' => 0,
            'errors' => [],
        ]);

    $this->assertDatabaseHas('suppliers', [
        'name' => 'Tech Supplier',
        'email' => 'tech@example.com',
        'category_id' => $this->category->id,
        'branch_id' => $this->branch->id,
    ]);
})->group('suppliers');

test('returns validation errors for invalid rows', function () {
    // Row 1: Valid
    // Row 2: Missing name
    // Row 3: Invalid status
    $csvContent = implode("\n", [
        'name,email,phone,address,branch,category,status',
        'Valid Supplier,valid@example.com,0812345,123 St,Head Office,IT Equipment,active',
        ',no_name@example.com,0812345,123 St,Head Office,IT Equipment,active',
        'Invalid Status Supplier,inv@example.com,0812345,123 St,Head Office,IT Equipment,invalid_status',
    ]);
    
    $file = UploadedFile::fake()->createWithContent('suppliers_invalid.csv', $csvContent);

    $response = $this->postJson('/api/suppliers/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'imported' => 1,
            'skipped' => 0,
        ]);
        
    $this->assertCount(2, $response->json('errors'));
    
    $errors = collect($response->json('errors'));
    $this->assertTrue($errors->contains(fn ($e) => $e['row'] == 3 && $e['field'] == 'Validation'));
    $this->assertTrue($errors->contains(fn ($e) => $e['row'] == 4 && $e['field'] == 'Validation'));
})->group('suppliers');

test('returns errors for unknown foreign keys', function () {
    $csvContent = implode("\n", [
        'name,email,phone,address,branch,category,status',
        'Supplier A,test@example.com,0812345,123 St,Head Office,Unknown Category,active',
    ]);
    
    $file = UploadedFile::fake()->createWithContent('suppliers_fk.csv', $csvContent);

    $response = $this->postJson('/api/suppliers/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200);
    $errors = collect($response->json('errors'));
    
    $this->assertTrue($errors->contains(fn ($e) => $e['field'] == 'category' && str_contains($e['message'], 'Unknown')));
})->group('suppliers');

test('skips/upserts existing supplier by email', function () {
    Supplier::factory()->create([
        'email' => 'exist@example.com',
        'name' => 'Old Name',
        'status' => 'inactive'
    ]);

    $csvContent = implode("\n", [
        'name,email,phone,address,branch,category,status',
        'New Name,exist@example.com,0812345,123 St,Head Office,IT Equipment,active',
    ]);
    
    $file = UploadedFile::fake()->createWithContent('suppliers_upsert.csv', $csvContent);

    $response = $this->postJson('/api/suppliers/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'imported' => 1,
            'errors' => [],
        ]);

    $this->assertDatabaseHas('suppliers', [
        'email' => 'exist@example.com',
        'name' => 'New Name',
        'status' => 'active',
    ]);
})->group('suppliers');

test('upserts existing supplier by name if email is absent', function () {
    Supplier::factory()->create([
        'email' => null,
        'name' => 'Unique Supplier Name',
        'status' => 'inactive'
    ]);

    $csvContent = implode("\n", [
        'name,email,phone,address,branch,category,status',
        'Unique Supplier Name,,0812345,123 St,Head Office,IT Equipment,active',
    ]);
    
    $file = UploadedFile::fake()->createWithContent('suppliers_upsert_name.csv', $csvContent);

    $response = $this->postJson('/api/suppliers/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'imported' => 1,
            'errors' => [],
        ]);

    $this->assertDatabaseHas('suppliers', [
        'name' => 'Unique Supplier Name',
        'status' => 'active',
    ]);
})->group('suppliers');

test('validates file type', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100);

    $response = $this->postJson('/api/suppliers/import', [
        'file' => $file,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
})->group('suppliers');
