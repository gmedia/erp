<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

trait HasRouteIgnoredUniqueRule
{
    protected function routeIgnoredUniqueRule(string $table, string $column, string $routeParameter): Unique
    {
        $rule = Rule::unique($table, $column);
        $ignoredRecord = $this->route($routeParameter);

        if ($ignoredRecord === null) {
            return $rule;
        }

        return $rule->ignore($ignoredRecord);
    }
}
