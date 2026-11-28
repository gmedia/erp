<?php

namespace App\Http\Requests\MobileAppReleases;

use App\Http\Requests\AuthorizedFormRequest;

class IndexMobileAppReleaseRequest extends AuthorizedFormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
