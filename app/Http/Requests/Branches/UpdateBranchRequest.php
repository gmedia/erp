<?php

namespace App\Http\Requests\Branches;

use App\Http\Requests\SimpleCrudUpdateRequest;
use App\Models\Branch;

class UpdateBranchRequest extends SimpleCrudUpdateRequest
{
    public function getModelClass(): string
    {
        return Branch::class;
    }
}
