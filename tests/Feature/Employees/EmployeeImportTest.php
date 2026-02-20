<?php

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    // Create user with necessary permissions using the helper function
    $this->user = createTestUserWithPermissions(['employee', 'employee.create']);
    $this->actingAs($this->user);

    $this->department = Department::factory()->create(['name' => 'IT']);
    $this->position = Position::factory()->create(['name' => 'Developer']);
    $this->branch = Branch::factory()->create(['name' => 'Head Office']);
});

test('can import employees from csv file', function () {
    // Header + 1 row
    $csvContent = implode("\n", [
        'employee_id,name,email,phone,department,position,branch,salary,hire_date,employment_status',
        'EMP-001,John Doe,john@example.com,08123456789,IT,Developer,Head Office,10000000,2023-01-01,regular',
    ]);
    
    $file = UploadedFile::fake()->createWithContent('employees.csv', $csvContent);

    $response = $this->postJson('/api/employees/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'imported' => 1,
            'skipped' => 0,
            'errors' => [],
        ]);

    $this->assertDatabaseHas('employees', [
        'email' => 'john@example.com',
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
        'branch_id' => $this->branch->id,
    ]);
})->group('employees');

test('returns validation errors for invalid rows', function () {
    // Row 1: Valid
    // Row 2: Missing email
    // Row 3: Invalid Date
    $csvContent = implode("\n", [
        'employee_id,name,email,phone,department,position,branch,salary,hire_date,employment_status',
        'EMP-002,Valid User,valid@example.com,0812345,IT,Developer,Head Office,5000000,2023-01-01,regular',
        'EMP-003,No Email,,0812345,IT,Developer,Head Office,5000000,2023-01-01,regular',
        'EMP-004,Invalid Date,inv@example.com,0812345,IT,Developer,Head Office,5000000,not-a-date,regular',
    ]);
    
    $file = UploadedFile::fake()->createWithContent('employees_invalid.csv', $csvContent);

    $response = $this->postJson('/api/employees/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'imported' => 1,
            'skipped' => 0,
        ]);
        
    $this->assertCount(2, $response->json('errors'));
    
    // Check specific errors
    $errors = collect($response->json('errors'));
    $this->assertTrue($errors->contains(fn ($e) => $e['row'] == 3 && $e['field'] == 'Validation')); // Row 3 is "No Email" (Header=1, Valid=2, NoEmail=3)
    $this->assertTrue($errors->contains(fn ($e) => $e['row'] == 4 && $e['field'] == 'Validation')); // Row 4 is "Invalid Date"
})->group('employees');

test('returns errors for unknown foreign keys', function () {
    $csvContent = implode("\n", [
        'employee_id,name,email,phone,department,position,branch,salary,hire_date,employment_status',
        'EMP-005,Unknown Dept,test@example.com,0812345,Unknown,Developer,Head Office,5000000,2023-01-01,regular',
    ]);
    
    $file = UploadedFile::fake()->createWithContent('employees_fk.csv', $csvContent);

    $response = $this->postJson('/api/employees/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200);
    $errors = collect($response->json('errors'));
    
    $this->assertTrue($errors->contains(fn ($e) => $e['field'] == 'department' && str_contains($e['message'], 'Unknown')));
})->group('employees');

test('skips/upserts existing email', function () {
    // Create existing employee
    Employee::factory()->create([
        'email' => 'exist@example.com',
        'name' => 'Old Name',
        'salary' => 5000000
    ]);

    $csvContent = implode("\n", [
        'employee_id,name,email,phone,department,position,branch,salary,hire_date,employment_status',
        'EMP-123,New Name,exist@example.com,0812345,IT,Developer,Head Office,9000000,2023-01-01,regular',
    ]);
    
    $file = UploadedFile::fake()->createWithContent('employees_upsert.csv', $csvContent);

    $response = $this->postJson('/api/employees/import', [
        'file' => $file,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'imported' => 1, // Upsert counts as imported/processed
            'errors' => [],
        ]);

    // Assert data updated
    $this->assertDatabaseHas('employees', [
        'email' => 'exist@example.com',
        'name' => 'New Name',
        'salary' => 9000000,
    ]);
})->group('employees');

test('validates file type', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100);

    $response = $this->postJson('/api/employees/import', [
        'file' => $file,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
})->group('employees');
