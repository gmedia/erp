<?php

namespace App\Http\Requests\Accounts;

use Illuminate\Validation\Rules\Unique;

class UpdateAccountRequest extends AbstractAccountRequest
{
    protected function accountCodeUniqueRule(): Unique
    {
        $account = $this->route('account');

        return $this->scopedAccountCodeUniqueRule()->ignore($account->id);
    }
}
