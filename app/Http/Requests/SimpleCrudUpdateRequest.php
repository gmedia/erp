<?php

namespace App\Http\Requests;

abstract class SimpleCrudUpdateRequest extends SimpleCrudNameRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:' . $this->modelTable() . ',name,' . $this->routeResourceId()],
        ];
    }
}
