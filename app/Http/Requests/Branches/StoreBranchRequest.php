<?php

namespace App\Http\Requests\Branches;

use App\Http\Requests\SimpleCrudStoreRequest;
use App\Models\Branch;

class StoreBranchRequest extends SimpleCrudStoreRequest
{
    public function getModelClass(): string
    {
        return Branch::class;
    }
}
