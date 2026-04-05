<?php

namespace App\Http\Requests;

class SimpleCrudIndexRequest extends SimpleCrudListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            $this->simpleCrudSortRules(),
            $this->paginationRules(),
        );
    }
}
