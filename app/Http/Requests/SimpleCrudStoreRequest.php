<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

abstract class SimpleCrudStoreRequest extends FormRequest
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
        $model = new ($this->getModelClass());
        
        return [
             'name' => ['required', 'string', 'max:255', 'unique:' . $model->getTable() . ',name'],
        ];
    }
}
