<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class AdminSettingRequest extends AuthorizedFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var array<int, string> $supportedCurrencies */
        $supportedCurrencies = config('app.supported_transaction_currencies', ['IDR']);

        return [
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:1000'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_email' => ['nullable', 'string', 'email', 'max:255'],
            'company_logo' => ['nullable', 'file', 'mimetypes:image/svg+xml', 'max:2048'],
            'company_logo_svg' => ['nullable', 'string', 'max:262144'],
            'timezone' => ['nullable', 'string', 'max:100', 'timezone:all'],
            'currency' => ['nullable', 'string', 'max:10', Rule::in($supportedCurrencies)],
            'date_format' => ['nullable', 'string', 'max:20'],
            'number_format_decimal' => ['nullable', 'string', 'max:5'],
            'number_format_thousand' => ['nullable', 'string', 'max:5'],
            'number_format_hide_decimal' => ['nullable', 'boolean'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'string', 'max:10'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_encryption' => ['nullable', 'string', 'max:20'],
            'mail_from_address' => ['nullable', 'string', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'test_email' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }
}
