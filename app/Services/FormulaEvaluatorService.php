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
        $expression = preg_replace('/\s+/', '', trim($expression)) ?? '';

        // Angka pertama boleh negatif (mis. total laba operasional negatif),
        // lalu diikuti pasangan operator + angka bertanda.
        if (! preg_match('/^([+-]?\d+(?:\.\d+)?)(.*)$/', $expression, $matches)) {
            return 0.0;
        }

        $result = (float) $matches[1];
        $remaining = $matches[2];

        while ($remaining !== '' && preg_match('/^([+-])([+-]?\d+(?:\.\d+)?)(.*)$/', $remaining, $parts)) {
            $operand = (float) $parts[2];
            $result = $parts[1] === '+' ? $result + $operand : $result - $operand;
            $remaining = $parts[3];
        }

        return round($result, 2);
    }
}
