<?php

namespace App\Http\Requests;

abstract class ImportFileRequest extends AuthorizedFormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ];
    }
}
