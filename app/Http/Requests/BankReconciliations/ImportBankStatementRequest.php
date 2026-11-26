<?php

namespace App\Http\Requests\BankReconciliations;

use App\Http\Requests\ImportFileRequest;

class ImportBankStatementRequest extends ImportFileRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'mapping' => ['required', 'array'],
            'mapping.date' => ['required', 'string'],
            'mapping.description' => ['required', 'string'],
            'mapping.amount' => ['nullable', 'string'],
            'mapping.debit' => ['nullable', 'string'],
            'mapping.credit' => ['nullable', 'string'],
            'mapping.reference' => ['nullable', 'string'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mapping.date.required' => __('form_validation.bank_mapping_date_required'),
            'mapping.description.required' => __('form_validation.bank_mapping_description_required'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $mapping = $this->input('mapping', []);
            $hasAmount = ! empty($mapping['amount']);
            $hasDebitCredit = ! empty($mapping['debit']) && ! empty($mapping['credit']);

            if (! $hasAmount && ! $hasDebitCredit) {
                $validator->errors()->add(
                    'mapping.amount',
                    'Either a single amount column or both debit and credit columns must be provided.'
                );
            }
        });
    }
}
