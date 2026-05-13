<?php

namespace App\Http\Requests\ReportConfigurations;

use App\Models\ReportConfiguration;
use App\Models\ReportSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('report_configurations', 'code')->ignore($this->route('report_configuration')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'report_type' => ['required', Rule::in(ReportConfiguration::TYPES)],
            'layout_config' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'sections' => ['nullable', 'array'],
            'sections.*.code' => ['required_with:sections', 'string', 'max:255'],
            'sections.*.name' => ['required_with:sections', 'string', 'max:255'],
            'sections.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'sections.*.section_type' => ['required_with:sections', Rule::in(ReportSection::SECTION_TYPES)],
            'sections.*.account_type_filter' => ['nullable', 'string', 'max:30'],
            'sections.*.account_sub_type_filter' => ['nullable', 'string', 'max:50'],
            'sections.*.sign_convention' => ['nullable', Rule::in(ReportSection::SIGN_CONVENTIONS)],
            'sections.*.formula' => ['nullable', 'string', 'max:255'],
            'sections.*.is_active' => ['boolean'],
            'sections.*.parent_code' => ['nullable', 'string', 'max:255'],
        ];
    }
}
