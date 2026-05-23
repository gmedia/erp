<?php

namespace App\Services;

use Illuminate\Support\Collection;

class FormulaEvaluatorService
{
    /**
     * @return array<string, float>
     */
    public function evaluate(Collection $sections): array
    {
        $values = [];
        $formulas = [];

        foreach ($sections as $section) {
            $code = $section['code'];

            if ($section['formula'] !== null && $section['formula'] !== '') {
                $formulas[$code] = $section['formula'];
            } else {
                $values[$code] = (float) $section['value'];
            }
        }

        $resolved = $this->resolveFormulas($values, $formulas);

        return array_merge($values, $resolved);
    }

    /**
     * @param  array<string, float>  $values
     * @param  array<string, string>  $formulas
     * @return array<string, float>
     */
    private function resolveFormulas(array $values, array $formulas): array
    {
        $resolved = [];
        $maxIterations = count($formulas) + 1;
        $iteration = 0;

        while (count($formulas) > 0 && $iteration < $maxIterations) {
            $iteration++;
            $progress = false;

            foreach ($formulas as $code => $formula) {
                $result = $this->tryEvaluate($formula, array_merge($values, $resolved));

                if ($result !== null) {
                    $resolved[$code] = $result;
                    unset($formulas[$code]);
                    $progress = true;
                }
            }

            if (! $progress) {
                break;
            }
        }

        foreach ($formulas as $code => $formula) {
            $resolved[$code] = 0.0;
        }

        return $resolved;
    }

    private function tryEvaluate(string $formula, array $values): ?float
    {
        $references = [];
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $formula, $matches);

        foreach ($matches[1] as $ref) {
            if (! array_key_exists($ref, $values)) {
                return null;
            }
            $references[$ref] = $values[$ref];
        }

        $expression = $formula;
        foreach ($references as $ref => $value) {
            $expression = str_replace('{' . $ref . '}', (string) $value, $expression);
        }

        return $this->evaluateArithmetic($expression);
    }

    private function evaluateArithmetic(string $expression): float
    {
        $expression = trim($expression);
        $tokens = preg_split('/\s*([+\-])\s*(?=\d)/', $expression, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if ($tokens === false || count($tokens) === 0) {
            return 0.0;
        }

        $result = (float) array_shift($tokens);

        while (count($tokens) >= 2) {
            $operator = array_shift($tokens);
            $operand = (float) array_shift($tokens);

            if ($operator === '+') {
                $result += $operand;
            } elseif ($operator === '-') {
                $result -= $operand;
            }
        }

        return round($result, 2);
    }
}
