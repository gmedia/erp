<?php

namespace App\Http\Requests\Customers;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'required|string',
            'branch' => 'required|exists:branches,id',
            'customer_type' => 'required|in:individual,company',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Get the validated data and map branch to FK column.
     */
    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        // Map branch to branch_id
        if (isset($validated['branch'])) {
            $validated['branch_id'] = $validated['branch'];
            unset($validated['branch']);
        }

        return $validated;
    }
}
