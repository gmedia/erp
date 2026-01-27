<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

abstract class SimpleCrudUpdateRequest extends FormRequest
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
        $modelClass = $this->getModelClass();
        $model = new $modelClass;
        $table = $model->getTable();
        
        // Try to find the route parameter that matches the model
        // Convention: model 'CustomerCategory' -> route param 'customer_category'
        $resourceName = \Illuminate\Support\Str::snake(class_basename($modelClass));
        $resourceId = $this->route($resourceName)?->id ?? $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:' . $table . ',name,' . $resourceId],
        ];
    }
}
