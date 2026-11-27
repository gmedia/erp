<?php

namespace App\Http\Requests\SecurityLogs;

use App\Http\Requests\BaseListingRequest;
use App\Models\SecurityLog;
use Illuminate\Validation\Rule;

abstract class AbstractSecurityLogListingRequest extends BaseListingRequest
{
    protected function securityLogListingRules(array $extraRules = []): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'event' => ['nullable', 'string', Rule::in([
                SecurityLog::EVENT_LOGIN_SUCCESS,
                SecurityLog::EVENT_LOGIN_FAILED,
                SecurityLog::EVENT_LOGOUT,
                SecurityLog::EVENT_PASSWORD_RESET,
                SecurityLog::EVENT_ROLE_CHANGED,
                SecurityLog::EVENT_USER_CREATED,
                SecurityLog::EVENT_USER_DEACTIVATED,
            ])],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            ...$this->listingSortRules('id,event,user_id,ip_address,created_at'),
            ...$extraRules,
        ];
    }
}
