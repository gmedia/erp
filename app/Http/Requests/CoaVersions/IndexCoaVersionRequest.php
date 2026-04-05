<?php

namespace App\Http\Requests\CoaVersions;

class IndexCoaVersionRequest extends AbstractCoaVersionListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->coaVersionListingRules('id,name,fiscal_year_id,fiscal_year.name,fiscal_year_name,status,created_at,updated_at'),
            $this->paginationRules(),
        );
    }
}
