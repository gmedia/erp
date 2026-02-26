<?php

use App\Models\Pipeline;
use App\Models\PipelineEntityState;
use App\Models\PipelineState;
use App\Models\PipelineStateLog;
use App\Models\PipelineTransition;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('pipeline-audit-trail');

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['pipeline_audit_trail']);
    $this->otherUser = createTestUserWithPermissions([]);

    $this->pipeline = Pipeline::factory()->create(['name' => 'Sales Pipeline']);
    
    $this->stateA = PipelineState::factory()->create(['pipeline_id' => $this->pipeline->id, 'name' => 'Lead']);
    $this->stateB = PipelineState::factory()->create(['pipeline_id' => $this->pipeline->id, 'name' => 'Qualified']);
    
    $this->transition = PipelineTransition::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'from_state_id' => $this->stateA->id,
        'to_state_id' => $this->stateB->id,
        'name' => 'Qualify Lead'
    ]);

    $this->entityState = PipelineEntityState::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'current_state_id' => $this->stateB->id,
        'entity_type' => 'App\Models\Customer',
        'entity_id' => '100',
    ]);

    $this->log = PipelineStateLog::create([
        'pipeline_entity_state_id' => $this->entityState->id,
        'entity_type' => 'App\Models\Customer',
        'entity_id' => '100',
        'from_state_id' => $this->stateA->id,
        'to_state_id' => $this->stateB->id,
        'transition_id' => $this->transition->id,
        'performed_by' => $this->user->id,
        'comment' => 'Customer looks promising.',
        'metadata' => ['score' => 85, 'source' => 'web'],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
    ]);
});

test('it requires permission to access the audit trail', function () {
    actingAs($this->otherUser)
        ->get(route('pipeline-audit-trail'))
        ->assertForbidden();

    actingAs($this->otherUser)
        ->getJson('/api/pipeline-audit-trail')
        ->assertForbidden();
});

test('it can render the pipeline audit trail page', function () {
    actingAs($this->user)
        ->get(route('pipeline-audit-trail'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('pipeline-audit-trail/index')
            ->has('logs.data', 1)
        );
});

test('it can fetch audit trail data via json', function () {
    actingAs($this->user)
        ->getJson('/api/pipeline-audit-trail')
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', 1)
            ->has('data.0', fn ($json) => $json
                ->where('entity_type', 'App\Models\Customer')
                ->where('entity_type_short', 'Customer')
                ->where('entity_id', 100)
                ->where('pipeline_name', 'Sales Pipeline')
                ->where('from_state_name', 'Lead')
                ->where('to_state_name', 'Qualified')
                ->where('transition_name', 'Qualify Lead')
                ->where('performed_by_name', $this->user->name)
                ->where('comment', 'Customer looks promising.')
                ->where('metadata.score', 85)
                ->etc()
            )
            ->has('meta')
            ->has('links')
        );
});

test('it can filter the report by various parameters', function () {
    // Modify log to be older
    $this->log->forceFill(['created_at' => '2025-01-01 00:00:00'])->saveQuietly();
    
    // Create another log record
    $otherPipeline = Pipeline::factory()->create(['name' => 'Support Tickets']);
    $otherState = PipelineState::factory()->create(['pipeline_id' => $otherPipeline->id, 'name' => 'Open']);
    
    $otherEntityState = PipelineEntityState::factory()->create([
        'pipeline_id' => $otherPipeline->id,
        'current_state_id' => $otherState->id,
        'entity_type' => 'App\Models\Ticket',
        'entity_id' => '999',
    ]);

    PipelineStateLog::create([
        'pipeline_entity_state_id' => $otherEntityState->id,
        'entity_type' => 'App\Models\Ticket',
        'entity_id' => '999',
        'from_state_id' => null,
        'to_state_id' => $otherState->id,
        'transition_id' => null,
        'performed_by' => $this->otherUser->id,
        'comment' => 'Created via email',
        'metadata' => [],
        'created_at' => now(),
    ]);

    // Filter by pipeline
    actingAs($this->user)
        ->getJson('/api/pipeline-audit-trail?pipeline_id=' . $this->pipeline->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.pipeline_name', 'Sales Pipeline');

    // Filter by entity type
    actingAs($this->user)
        ->getJson('/api/pipeline-audit-trail?entity_type=Ticket')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.entity_type_short', 'Ticket');
        
    // Filter by performed by
    actingAs($this->user)
        ->getJson('/api/pipeline-audit-trail?performed_by=' . $this->otherUser->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.performed_by_name', $this->otherUser->name);
        
    // Filter by search term
    actingAs($this->user)
        ->getJson('/api/pipeline-audit-trail?search=email')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.comment', 'Created via email');

    // Filter by date range
    actingAs($this->user)
        ->getJson('/api/pipeline-audit-trail?start_date=2025-12-01&end_date=2026-12-31')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.entity_type_short', 'Ticket'); // 1st log is from 2025-01-01
});

test('it can export the audit trail data to excel', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-01 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    $response = actingAs($this->user)
        ->postJson('/api/pipeline-audit-trail/export')
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('pipeline_audit_trail_2026-02-01_10-00-00_');
    expect($filename)->toEndWith('.xlsx');
    
    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});
