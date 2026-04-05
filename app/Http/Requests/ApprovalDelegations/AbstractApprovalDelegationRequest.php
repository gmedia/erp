<?php

namespace App\Http\Requests\ApprovalDelegations;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasSometimesArrayRules;

abstract class AbstractApprovalDelegationRequest extends AuthorizedFormRequest
{
    use HasSometimesArrayRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'delegator_user_id' => $this->withSometimes(['required', 'exists:users,id']),
            'delegate_user_id' => $this->withSometimes(['required', 'exists:users,id', 'different:delegator_user_id']),
            'approvable_type' => $this->withSometimes(['nullable', 'string', 'max:255']),
            'start_date' => $this->withSometimes(['required', 'date']),
            'end_date' => $this->withSometimes(['required', 'date', 'after_or_equal:start_date']),
            'reason' => $this->withSometimes(['nullable', 'string', 'max:255']),
            'is_active' => $this->withSometimes(['boolean']),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    abstract protected function usesSometimes(): bool;
}
