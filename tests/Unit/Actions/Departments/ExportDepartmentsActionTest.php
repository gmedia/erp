<?php

use App\Actions\Departments\ExportDepartmentsAction;
use App\Http\Requests\Departments\ExportDepartmentRequest;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('departments');

test('execute generates excel file and returns url', function () {
    Carbon::setTestNow(now());
    Excel::fake();
    Storage::fake('public');
    
    Department::factory()->count(3)->create();

    $action = new ExportDepartmentsAction();
    $request = Mockery::mock(ExportDepartmentRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'search' => null,
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ]);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    
    $result = $action->execute($request);

    $filename = 'departments_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    expect($result->getStatusCode())->toBe(200)
        ->and($result->getData(true))->toHaveKeys(['url', 'filename'])
        ->and($result->getData(true)['filename'])->toBe($filename);

    Excel::assertStored('exports/' . $filename, 'public');
});

test('execute filters export by search term', function () {
    Carbon::setTestNow(now());
    Excel::fake();
    Storage::fake('public');
    
    Department::factory()->create(['name' => 'IT Department']);
    Department::factory()->create(['name' => 'HR Department']);

    $action = new ExportDepartmentsAction();
    $request = Mockery::mock(ExportDepartmentRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'search' => 'IT',
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ]);
    $request->shouldReceive('filled')->with('search')->andReturn(true);
    
    $action->execute($request);
    
    $filename = 'departments_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
    Excel::assertStored('exports/' . $filename, 'public');
});
