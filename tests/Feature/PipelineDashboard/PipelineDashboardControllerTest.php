<?php

use App\Models\Asset;
use App\Models\Pipeline;
use App\Models\PipelineEntityState;
use App\Models\PipelineState;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('pipeline-dashboard');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['pipeline_dashboard']);
    
    // Create pipeline setup
    $this->pipeline = Pipeline::factory()->create([
        'entity_type' => Asset::class,
        'is_active' => true,
    ]);

    $this->stateDraft = PipelineState::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'code' => 'draft',
        'name' => 'Draft',
        'type' => 'initial',
        'sort_order' => 1,
    ]);

    $this->stateReview = PipelineState::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'code' => 'review',
        'name' => 'Review',
        'type' => 'intermediate',
        'sort_order' => 2,
    ]);

    $this->stateActive = PipelineState::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'code' => 'active',
        'name' => 'Active',
        'type' => 'final',
        'sort_order' => 3,
    ]);
});

it('requires authentication and permission to access the dashboard', function () {
    // Unauthenticated
    $this->get('/pipeline-dashboard')->assertRedirect('/login');

    // Authenticated without permission
    $userWithoutPermission = createTestUserWithPermissions([]);
    actingAs($userWithoutPermission)
        ->get('/pipeline-dashboard')
        ->assertForbidden();

    // Authenticated with permission
    actingAs($this->user)
        ->get('/pipeline-dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('pipeline-dashboard/index')
            ->has('pipelines', 1)
        );
});

it('returns correct summary data via API', function () {
    // Create some entity states
    PipelineEntityState::factory()->count(2)->sequence(
        ['entity_id' => 1],
        ['entity_id' => 2]
    )->create([
        'pipeline_id' => $this->pipeline->id,
        'entity_type' => Asset::class,
        'current_state_id' => $this->stateDraft->id,
    ]);

    PipelineEntityState::factory()->count(3)->sequence(
        ['entity_id' => 3],
        ['entity_id' => 4],
        ['entity_id' => 5]
    )->create([
        'pipeline_id' => $this->pipeline->id,
        'entity_type' => Asset::class,
        'current_state_id' => $this->stateReview->id,
    ]);

    $response = actingAs($this->user)->getJson('/api/pipeline-dashboard/data');

    $response->assertOk()
        ->assertJsonStructure([
            'summary' => [
                '*' => ['state_id', 'name', 'code', 'color', 'count']
            ],
            'stale_entities'
        ]);

    $data = $response->json();
    
    // Order based on states retrieved from pipeline
    expect($data['summary'][0]['count'])->toBe(2); // Draft
    expect($data['summary'][1]['count'])->toBe(3); // Review
    expect($data['summary'][2]['count'])->toBe(0); // Active
});

it('filters data by pipeline_id', function () {
    $pipeline2 = Pipeline::factory()->create([
        'entity_type' => Asset::class,
        'is_active' => true,
    ]);
    
    $state2Draft = PipelineState::factory()->create([
        'pipeline_id' => $pipeline2->id,
        'code' => 'draft',
        'type' => 'initial',
    ]);

    // Data for pipeline 1
    PipelineEntityState::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'current_state_id' => $this->stateDraft->id,
        'entity_type' => Asset::class,
        'entity_id' => 1,
    ]);

    // Data for pipeline 2
    PipelineEntityState::factory()->count(4)->sequence(
        ['entity_id' => 2],
        ['entity_id' => 3],
        ['entity_id' => 4],
        ['entity_id' => 5]
    )->create([
        'pipeline_id' => $pipeline2->id,
        'current_state_id' => $state2Draft->id,
        'entity_type' => Asset::class,
    ]);

    // Request with filter for Pipeline 1
    $response1 = actingAs($this->user)->getJson("/api/pipeline-dashboard/data?pipeline_id={$this->pipeline->id}");
    $data1 = $response1->json();
    
    // Only states from pipeline 1 should be present in summary
    expect(count($data1['summary']))->toBe(3); // Pipeline 1 has 3 states
    // The sum in pipeline 1 is 1
    $totalCount1 = collect($data1['summary'])->sum('count');
    expect($totalCount1)->toBe(1);

    // Request with filter for Pipeline 2
    $response2 = actingAs($this->user)->getJson("/api/pipeline-dashboard/data?pipeline_id={$pipeline2->id}");
    $data2 = $response2->json();
    
    expect(count($data2['summary']))->toBe(1); // Pipeline 2 has 1 state
    $totalCount2 = collect($data2['summary'])->sum('count');
    expect($totalCount2)->toBe(4);
});

it('detects stale entities based on threshold', function () {
    // Fresh entity (not stale)
    PipelineEntityState::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'current_state_id' => $this->stateReview->id,
        'last_transitioned_at' => Carbon::now()->subDays(2),
        'entity_type' => Asset::class,
        'entity_id' => 1,
    ]);

    // Stale entity (stale)
    $staleEntity = PipelineEntityState::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'current_state_id' => $this->stateReview->id,
        'last_transitioned_at' => Carbon::now()->subDays(10),
        'entity_type' => Asset::class,
        'entity_id' => 2,
    ]);
    
    // Default threshold is 7 days
    $response = actingAs($this->user)->getJson('/api/pipeline-dashboard/data');
    
    $response->assertOk();
    $data = $response->json();
    
    expect(count($data['stale_entities']))->toBe(1);
    expect($data['stale_entities'][0]['id'])->toBe($staleEntity->id);
    expect($data['stale_entities'][0]['days_in_state'])->toBeGreaterThanOrEqual(10);
});
