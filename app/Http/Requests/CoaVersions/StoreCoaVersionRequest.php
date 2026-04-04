<?php

namespace App\Http\Requests\CoaVersions;

class StoreCoaVersionRequest extends AbstractCoaVersionRequest
{
    protected function usesSometimes(): bool
    {
        return false;
    }
}
