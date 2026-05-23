<?php

use App\Actions\Reports\EvaluateReportSectionsAction;
use App\Services\FormulaEvaluatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('formula-evaluator');

test('FormulaEvaluatorService evaluates simple arithmetic', function () {
    $service = new FormulaEvaluatorService;

    $sections = collect([
        ['code' => 'revenue', 'value' => 5000000.0, 'formula' => null],
        ['code' => 'expense', 'value' => 3000000.0, 'formula' => null],
        ['code' => 'net_income', 'value' => 0.0, 'formula' => '{revenue} - {expense}'],
    ]);

    $result = $service->evaluate($sections);

    expect($result['revenue'])->toBe(5000000.0)
        ->and($result['expense'])->toBe(3000000.0)
        ->and($result['net_income'])->toBe(2000000.0);
});

test('FormulaEvaluatorService evaluates chained formulas', function () {
    $service = new FormulaEvaluatorService;

    $sections = collect([
        ['code' => 'total_revenue', 'value' => 10000000.0, 'formula' => null],
        ['code' => 'cogs', 'value' => 4000000.0, 'formula' => null],
        ['code' => 'operating_expense', 'value' => 2000000.0, 'formula' => null],
        ['code' => 'gross_profit', 'value' => 0.0, 'formula' => '{total_revenue} - {cogs}'],
        ['code' => 'operating_income', 'value' => 0.0, 'formula' => '{gross_profit} - {operating_expense}'],
    ]);

    $result = $service->evaluate($sections);

    expect($result['gross_profit'])->toBe(6000000.0)
        ->and($result['operating_income'])->toBe(4000000.0);
});

test('FormulaEvaluatorService evaluates addition formulas', function () {
    $service = new FormulaEvaluatorService;

    $sections = collect([
        ['code' => 'liabilities', 'value' => 3000000.0, 'formula' => null],
        ['code' => 'equity', 'value' => 5000000.0, 'formula' => null],
        ['code' => 'total_liab_equity', 'value' => 0.0, 'formula' => '{liabilities} + {equity}'],
    ]);

    $result = $service->evaluate($sections);

    expect($result['total_liab_equity'])->toBe(8000000.0);
});

test('FormulaEvaluatorService handles unresolvable references gracefully', function () {
    $service = new FormulaEvaluatorService;

    $sections = collect([
        ['code' => 'broken', 'value' => 0.0, 'formula' => '{nonexistent_section} + {also_missing}'],
    ]);

    $result = $service->evaluate($sections);

    expect($result['broken'])->toBe(0.0);
});

test('FormulaEvaluatorService handles multi-term formulas', function () {
    $service = new FormulaEvaluatorService;

    $sections = collect([
        ['code' => 'operating', 'value' => 1000000.0, 'formula' => null],
        ['code' => 'investing', 'value' => -500000.0, 'formula' => null],
        ['code' => 'financing', 'value' => 200000.0, 'formula' => null],
        ['code' => 'net_change', 'value' => 0.0, 'formula' => '{operating} + {investing} + {financing}'],
    ]);

    $result = $service->evaluate($sections);

    expect($result['net_change'])->toBe(700000.0);
});

test('EvaluateReportSectionsAction computes section values from report totals', function () {
    $action = app(EvaluateReportSectionsAction::class);

    $sections = [
        ['code' => 'revenue_header', 'name' => 'REVENUE', 'section_type' => 'header', 'account_type_filter' => null, 'account_sub_type_filter' => null, 'sign_convention' => 'normal', 'formula' => null, 'sort_order' => 1],
        ['code' => 'total_revenue', 'name' => 'Total Revenue', 'section_type' => 'subtotal', 'account_type_filter' => 'revenue', 'account_sub_type_filter' => null, 'sign_convention' => 'normal', 'formula' => null, 'sort_order' => 2],
        ['code' => 'cost_of_goods_sold', 'name' => 'COGS', 'section_type' => 'detail', 'account_type_filter' => 'expense', 'account_sub_type_filter' => 'cost_of_goods_sold', 'sign_convention' => 'normal', 'formula' => null, 'sort_order' => 3],
        ['code' => 'gross_profit', 'name' => 'Gross Profit', 'section_type' => 'subtotal', 'account_type_filter' => null, 'account_sub_type_filter' => null, 'sign_convention' => 'normal', 'formula' => '{total_revenue} - {cost_of_goods_sold}', 'sort_order' => 4],
    ];

    $reportTotals = [
        'revenue' => 10000000,
        'expense' => 7000000,
        'cost_of_goods_sold' => 4000000,
    ];

    $result = $action->execute($sections, $reportTotals);

    expect($result[0]['value'])->toBe(0.0)
        ->and($result[1]['value'])->toBe(10000000.0)
        ->and($result[2]['value'])->toBe(4000000.0)
        ->and($result[3]['value'])->toBe(6000000.0);
});

test('EvaluateReportSectionsAction applies sign_convention reversed', function () {
    $action = app(EvaluateReportSectionsAction::class);

    $sections = [
        ['code' => 'expense', 'name' => 'Expense', 'section_type' => 'detail', 'account_type_filter' => 'expense', 'account_sub_type_filter' => null, 'sign_convention' => 'reversed', 'formula' => null, 'sort_order' => 1],
    ];

    $reportTotals = ['expense' => 3000000];

    $result = $action->execute($sections, $reportTotals);

    expect($result[0]['value'])->toBe(-3000000.0);
});
