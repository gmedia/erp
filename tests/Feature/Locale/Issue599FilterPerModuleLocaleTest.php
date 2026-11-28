<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

test('issue 599 penutup filter keys added for per-module files exist in both locales', function () {
    $en = issue599LocaleJson('en.json');
    $id = issue599LocaleJson('id.json');

    $expected = [
        'filter_field.actor' => ['Actor', 'Pelaku'],
        'filter_field.performed_by' => ['Performed By', 'Dijalankan oleh'],
        'filter_field.document_type' => ['Document Type', 'Jenis Dokumen'],
        'filter_field.entity_type' => ['Entity Type', 'Jenis Entitas'],
        'filter_field.approvable_type' => ['Approvable Type', 'Jenis Persetujuan'],
        'filter_field.all_entities' => ['All Entities', 'Semua Entitas'],
        'filter_field.start_date_from' => ['Start Date From', 'Tanggal Mulai Dari'],
        'filter_field.start_date_to' => ['Start Date To', 'Tanggal Mulai Sampai'],
        'filter_field.report_type' => ['Report Type', 'Jenis Laporan'],
        'filter_field.select_report_type' => ['Select Report Type', 'Pilih jenis laporan'],
        'filter_field.search_ellipsis' => ['Search...', 'Cari...'],
        'filter_field.as_of' => ['As of', 'Per'],
    ];

    foreach ($expected as $key => [$enText, $idText]) {
        expect(issue599LocaleLookup($en, $key))->toBe($enText, "en {$key}")
            ->and(issue599LocaleLookup($id, $key))->toBe($idText, "id {$key}");
    }
});

test('issue 599 penutup filter files no longer hardcode english labels or placeholders', function () {
    $files = [
        'js/components/approval-audit-trail/Filters.tsx',
        'js/components/pipeline-audit-trail/Filters.tsx',
        'js/components/approval-flows/ApprovalFlowFilters.tsx',
        'js/components/pipelines/PipelineFilters.tsx',
        'js/components/report-configurations/ReportConfigurationFilters.tsx',
        'js/components/approval-delegations/ApprovalDelegationFilters.tsx',
        'js/components/aging-dashboard/AgingFilters.tsx',
        'js/components/security-logs/Filters.tsx',
    ];

    foreach ($files as $file) {
        $source = File::get(resource_path($file));

        // Literal polos sebagai label/placeholder (bukan { key, fallback }).
        preg_match_all("/\\b(?:label|placeholder)\\s*:\\s*'([A-Z][^']*)'/", $source, $literal);
        preg_match_all("/createTextFilterField\\(\\s*'search',\\s*'Search'/", $source, $searchLiteral);

        expect($literal[1])->toBe([], $file)
            ->and($searchLiteral[0])->toBe([], $file);
    }
});
