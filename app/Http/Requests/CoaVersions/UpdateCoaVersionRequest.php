<?php

namespace App\Http\Requests\CoaVersions;

class UpdateCoaVersionRequest extends AbstractCoaVersionRequest
{
    protected function usesSometimes(): bool
    {
        return true;
    }
}
