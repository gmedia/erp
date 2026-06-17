<?php

namespace App\Http\Requests\MyApprovals;

use Illuminate\Foundation\Http\FormRequest;

class ApproveMyApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'comments' => ['nullable', 'string'],
        ];
    }
}
