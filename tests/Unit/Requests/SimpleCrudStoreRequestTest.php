<?php

use App\Http\Requests\SimpleCrudStoreRequest;
use App\Models\Department;

uses()->group('requests');

test('simple crud store request returns shared name rules', function () {
    $request = new class extends SimpleCrudStoreRequest
    {
        protected function getModelClass(): string
        {
            return Department::class;
        }
    };

    expect($request->authorize())->toBeTrue()
        ->and($request->rules())->toEqual([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
        ]);
});
