<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, string>
 */
function localePipelineDashboardTranslations(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return $decoded['pipelineDashboard'] ?? [];
}

test('pipeline dashboard translation keys exist in both english and indonesian locales', function () {
    $en = localePipelineDashboardTranslations('en.json');
    $id = localePipelineDashboardTranslations('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_diff(array_keys($en), array_keys($id)))->toBe([]);
    expect(array_diff(array_keys($id), array_keys($en)))->toBe([]);
});

test('pipeline dashboard english labels keep the original english text', function () {
    $en = localePipelineDashboardTranslations('en.json');

    expect($en['branch'])->toBe('Branch')
        ->and($en['allBranches'])->toBe('All Branches')
        ->and($en['selectPipeline'])->toBe('Select Pipeline')
        ->and($en['allActivePipelines'])->toBe('All Active Pipelines')
        ->and($en['staleThreshold'])->toBe('Stale Threshold')
        ->and($en['daysCount'])->toBe('{count} Days')
        ->and($en['staleEntities'])->toBe('Stale Entities')
        ->and($en['detected'])->toBe('detected')
        ->and($en['type'])->toBe('Type')
        ->and($en['entity'])->toBe('Entity')
        ->and($en['currentState'])->toBe('Current State')
        ->and($en['lastTransition'])->toBe('Last Transition')
        ->and($en['daysStale'])->toBe('Days Stale')
        ->and($en['noStaleEntities'])->toBe('No stale entities found.')
        ->and($en['stateDistribution'])->toBe('State Distribution')
        ->and($en['noEntitiesFound'])->toBe('No entities found.')
        ->and($en['noEntitiesYet'])->toBe('No entities in any state yet')
        ->and($en['loadingState'])->toBe('Loading State...');
});

test('pipeline dashboard labels are translated to indonesian', function () {
    $en = localePipelineDashboardTranslations('en.json');
    $id = localePipelineDashboardTranslations('id.json');

    foreach (array_keys($en) as $field) {
        expect($id[$field])->not->toBe($en[$field], "pipelineDashboard.{$field} not translated");
    }

    expect($id['allBranches'])->toBe('Semua Cabang')
        ->and($id['daysCount'])->toBe('{count} Hari')
        ->and($id['staleEntities'])->toBe('Entitas Macet');
});

test('pipeline dashboard components use the translation keys with english fallbacks', function () {
    $components = [
        'resources/js/components/pipeline-dashboard/PipelineFilter.tsx' => [
            'pipelineDashboard.branch',
            'pipelineDashboard.allBranches',
            'pipelineDashboard.selectPipeline',
            'pipelineDashboard.allActivePipelines',
            'pipelineDashboard.staleThreshold',
            'pipelineDashboard.daysCount',
        ],
        'resources/js/components/pipeline-dashboard/StaleEntitiesTable.tsx' => [
            'pipelineDashboard.staleEntities',
            'pipelineDashboard.staleDescription',
            'pipelineDashboard.detected',
            'pipelineDashboard.noStaleEntities',
            'pipelineDashboard.allMovingSmoothly',
            'pipelineDashboard.daysStale',
        ],
        'resources/js/components/pipeline-dashboard/StateDistributionChart.tsx' => [
            'pipelineDashboard.stateDistribution',
            'pipelineDashboard.entitiesMapped',
            'pipelineDashboard.noEntitiesFound',
            'pipelineDashboard.visualBreakdown',
            'pipelineDashboard.total',
        ],
        'resources/js/components/pipeline-dashboard/StateSummaryCards.tsx' => [
            'pipelineDashboard.loadingState',
            'pipelineDashboard.noStateData',
            'pipelineDashboard.noEntitiesYet',
            'pipelineDashboard.stateAppears',
        ],
    ];

    foreach ($components as $path => $keys) {
        $source = File::get(base_path($path));

        foreach ($keys as $key) {
            expect($source)->toContain("'{$key}'");
        }
    }
});
