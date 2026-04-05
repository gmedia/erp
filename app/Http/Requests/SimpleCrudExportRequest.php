<?php

namespace App\Http\Requests;

class SimpleCrudExportRequest extends SimpleCrudListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            $this->simpleCrudSortRules(),
        );
    }
}
