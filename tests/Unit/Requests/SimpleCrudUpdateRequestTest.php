<?php

use App\Http\Requests\SimpleCrudUpdateRequest;
use App\Models\Department;

uses()->group('requests');

test('simple crud update request returns shared name rules', function () {
    $department = Department::factory()->create();

    $request = new class extends SimpleCrudUpdateRequest
    {
        protected function getModelClass(): string
        {
            return Department::class;
        }
    };

    $request->setRouteResolver(function () use ($department) {
        return new class($department)
        {
            public function __construct(private Department $department) {}

            public function parameter(string $name): mixed
            {
                return $name === 'department' ? $this->department : null;
            }
        };
    });

    expect($request->authorize())->toBeTrue()
        ->and($request->rules())->toEqual([
            'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:departments,name,' . $department->id],
        ]);
});
