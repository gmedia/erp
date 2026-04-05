<?php

namespace App\Http\Requests\CoaVersions;

class ExportCoaVersionRequest extends AbstractCoaVersionListingRequest
{
    public function rules(): array
    {
        return $this->coaVersionListingRules();
    }
}
