<?php

namespace App\Http\Requests\Employees;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractEmployeeListingRequest extends BaseListingRequest
{
    protected function employeeListingRules(
        string $sortBy,
        array $additionalRules = [],
        bool $includeIntegerRules = false,
    ): array {
        return array_merge(
            $this->searchRules(),
            [
                'department_id' => $this->employeeRelationRules('departments', $includeIntegerRules),
                'position_id' => $this->employeeRelationRules('positions', $includeIntegerRules),
                'branch_id' => $this->employeeRelationRules('branches', $includeIntegerRules),
                'employment_status' => ['nullable', 'string', 'in:regular,intern'],
            ],
            $additionalRules,
            $this->listingSortRules($sortBy),
        );
    }

    /**
     * @return array<int, string>
     */
    private function employeeRelationRules(string $table, bool $includeIntegerRules): array
    {
        $rules = ['nullable'];

        if ($includeIntegerRules) {
            $rules[] = 'integer';
        }

        $rules[] = 'exists:' . $table . ',id';

        return $rules;
    }
}
