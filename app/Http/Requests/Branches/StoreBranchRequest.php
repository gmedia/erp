<?php

namespace App\Http\Requests\Branches;

use App\Models\Branch;
use App\Http\Requests\SimpleCrudStoreRequest;

class StoreBranchRequest extends SimpleCrudStoreRequest
{
    public function getModelClass(): string
    {
        return Branch::class;
    }
}
