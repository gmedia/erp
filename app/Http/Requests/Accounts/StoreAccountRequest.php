<?php

namespace App\Http\Requests\Accounts;

use Illuminate\Validation\Rules\Unique;

class StoreAccountRequest extends AbstractAccountRequest
{
    protected function accountCodeUniqueRule(): Unique
    {
        return $this->scopedAccountCodeUniqueRule();
    }
}
