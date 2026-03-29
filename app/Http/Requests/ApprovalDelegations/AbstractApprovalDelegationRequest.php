<?php

namespace App\Http\Requests\ApprovalDelegations;

use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractApprovalDelegationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'delegator_user_id' => $this->fieldRules(['required', 'exists:users,id']),
            'delegate_user_id' => $this->fieldRules(['required', 'exists:users,id', 'different:delegator_user_id']),
            'approvable_type' => $this->fieldRules(['nullable', 'string', 'max:255']),
            'start_date' => $this->fieldRules(['required', 'date']),
            'end_date' => $this->fieldRules(['required', 'date', 'after_or_equal:start_date']),
            'reason' => $this->fieldRules(['nullable', 'string', 'max:255']),
            'is_active' => $this->fieldRules(['boolean']),
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

    abstract protected function useSometimesRules(): bool;

    /**
     * @param array<int, string> $rules
     * @return array<int, string>
     */
    private function fieldRules(array $rules): array
    {
        if (! $this->useSometimesRules()) {
            return $rules;
        }

        return ['sometimes', ...$rules];
    }
}