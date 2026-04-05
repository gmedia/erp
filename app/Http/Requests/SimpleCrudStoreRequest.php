<?php

namespace App\Http\Requests;

abstract class SimpleCrudStoreRequest extends SimpleCrudNameRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:' . $this->modelTable() . ',name'],
        ];
    }
}
