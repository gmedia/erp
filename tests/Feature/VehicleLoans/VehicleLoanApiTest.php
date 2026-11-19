<?php

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\NotificationPreference;
use App\Models\VehicleDriver;
use App\Models\VehicleLoan;
use App\Models\VehicleUnit;
use App\Notifications\GenericAppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Traits\CreatesTestUserWithPermissions;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class, CreatesTestUserWithPermissions::class)->group('vehicle-loans');

beforeEach(function () {
    Mail::fake();
    Storage::fake('local');
    config()->set('vehicle_loan.photo_disk', 'local');

    $this->user = $this->createTestUserWithPermissions([
        'vehicle_loan',
        'vehicle_loan.request',
        'vehicle_loan.pickup',
        'vehicle_loan.return',
        'vehicle_loan.security',
        'vehicle_loan.report',
        'vehicle_loan.master',
        'my_approvals',
    ]);

    Sanctum::actingAs($this->user, ['*']);
});

it('manages vehicle drivers through the master API', function () {
    $employee = Employee::factory()->create();

    $created = postJson('/api/vehicle-drivers', [
        'employee_id' => $employee->id,
        'license_number' => 'SIM-111',
        'license_type' => 'B',
        'license_expiry' => now()->addYears(2)->toDateString(),
        'is_active' => true,
    ])->assertCreated();

    $id = $created->json('data.id');

    getJson('/api/vehicle-drivers')->assertOk();
    getJson("/api/vehicle-drivers/{$id}")->assertOk();

    putJson("/api/vehicle-drivers/{$id}", [
        'employee_id' => $employee->id,
        'license_number' => 'SIM-222',
        'license_type' => 'B',
        'license_expiry' => now()->addYears(3)->toDateString(),
        'is_active' => false,
    ])->assertOk()->assertJsonPath('data.license_number', 'SIM-222');

    deleteJson("/api/vehicle-drivers/{$id}")->assertNoContent();
});

it('manages vehicle units through the master API', function () {
    $asset = Asset::factory()->create();

    $created = postJson('/api/vehicle-units', [
        'asset_id' => $asset->id,
        'plate_number' => 'AB1234CD',
        'vehicle_type' => 'car',
        'capacity' => 4,
        'is_active' => true,
    ])->assertCreated();

    $id = $created->json('data.id');

    getJson('/api/vehicle-units')->assertOk();
    getJson("/api/vehicle-units/{$id}")->assertOk();

    putJson("/api/vehicle-units/{$id}", [
        'asset_id' => $asset->id,
        'plate_number' => 'AB9999ZZ',
        'vehicle_type' => 'car',
        'capacity' => 6,
        'is_active' => false,
    ])->assertOk()->assertJsonPath('data.plate_number', 'AB9999ZZ');

    deleteJson("/api/vehicle-units/{$id}")->assertNoContent();
});

it('exposes mobile config and assignment options', function () {
    VehicleDriver::factory()->create();
    VehicleUnit::factory()->create();

    getJson('/api/vehicle-loans/mobile-config')
        ->assertOk()
        ->assertJsonStructure(['public_base_url', 'qr_paths']);

    getJson('/api/vehicle-loans/assignment-options')
        ->assertOk()
        ->assertJsonStructure([
            'drivers' => [['id', 'name', 'license_expiry']],
            'units' => [['id', 'plate_number', 'vehicle_type']],
        ]);
});

it('lists filters shows updates and cancels loans', function () {
    $loan = VehicleLoan::factory()->create([
        'requester_id' => $this->user->id,
        'status' => 'draft',
    ]);

    getJson('/api/vehicle-loans')->assertOk();
    getJson('/api/vehicle-loans?status=draft&mine=1')->assertOk();
    getJson('/api/vehicle-loans/my')->assertOk();
    getJson("/api/vehicle-loans/{$loan->id}")->assertOk();

    putJson("/api/vehicle-loans/{$loan->id}", [
        'purpose' => 'Perjalanan dinas ke cabang',
    ])->assertOk()->assertJsonPath('data.purpose', 'Perjalanan dinas ke cabang');

    deleteJson("/api/vehicle-loans/{$loan->id}")->assertOk();

    $this->assertDatabaseHas('vehicle_loans', [
        'id' => $loan->id,
        'status' => 'cancelled',
    ]);
});

it('shows the security queue with derived requester unit and driver names', function () {
    $loan = VehicleLoan::factory()->create(['status' => 'approved']);

    getJson('/api/vehicle-loans/security-queue')
        ->assertOk()
        ->assertJsonPath('data.0.id', $loan->id)
        ->assertJsonPath('data.0.requester_name', $loan->requester->name)
        ->assertJsonPath('data.0.driver_name', $loan->driver?->employee?->name)
        ->assertJsonPath('data.0.plate_number', $loan->vehicleUnit?->plate_number)
        ->assertJsonStructure(['data' => [['id', 'status', 'requester_name', 'driver_name', 'plate_number']]]);
});

it('completes pickup, key handover and return', function () {
    $loan = VehicleLoan::factory()->create([
        'requester_id' => $this->user->id,
        'status' => 'approved',
    ]);

    postJson("/api/vehicle-loans/{$loan->id}/pickups", [
        'photo_mode' => 'with_driver',
        'selfie_photo' => UploadedFile::fake()->image('selfie.jpg'),
    ])->assertCreated();

    $pickup = $loan->fresh()->pickup;
    expect($pickup)->not->toBeNull();

    $flow = ApprovalFlow::create([
        'code' => 'vehicle-loan.handover-test',
        'name' => 'Handover Test',
        'approvable_type' => VehicleLoan::class,
        'is_active' => true,
        'created_by' => $this->user->id,
    ]);

    ApprovalRequest::create([
        'approval_flow_id' => $flow->id,
        'approvable_type' => VehicleLoan::class,
        'approvable_id' => $loan->id,
        'current_step_order' => 1,
        'status' => 'approved',
        'submitted_by' => $this->user->id,
        'submitted_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    $security = $this->createTestUserWithPermissions([
        'vehicle_loan', 'vehicle_loan.security',
    ]);
    Sanctum::actingAs($security, ['*']);

    postJson("/api/vehicle-loans/pickups/{$pickup->id}/handover")->assertOk();
    expect($pickup->fresh()->key_handover_at)->not->toBeNull();

    Sanctum::actingAs($this->user, ['*']);

    postJson("/api/vehicle-loans/pickups/{$pickup->id}/returns", [
        'handover_photo' => UploadedFile::fake()->image('handover.jpg'),
        'condition_photo' => UploadedFile::fake()->image('condition.jpg'),
        'incident' => false,
        'odometer_start' => 12000,
        'odometer_end' => 12345,
    ])->assertCreated();

    expect($loan->fresh()->status)->toBe('returned');
});

it('streams private photos only to authorized users', function () {
    $loan = VehicleLoan::factory()->create([
        'requester_id' => $this->user->id,
        'approved_by' => $this->user->id,
        'status' => 'pickup_done',
    ]);

    Storage::disk('local')->put('vehicle-loans/pickups/test.jpg', 'image-bytes');

    $pickup = $loan->pickup()->create([
        'requester_id' => $this->user->id,
        'photo_mode' => 'with_driver',
        'selfie_photo_path' => 'vehicle-loans/pickups/test.jpg',
        'selfie_photo_name' => 'selfie.jpg',
        'picked_at' => now(),
    ]);

    get("/api/vehicle-loans/photos/pickups/{$pickup->id}")->assertOk();

    $this->assertDatabaseHas('vehicle_loan_activity_logs', [
        'vehicle_loan_id' => $loan->id,
        'event' => 'pickup_photo_viewed',
    ]);

    $other = $this->createTestUserWithPermissions(['vehicle_loan']);
    Sanctum::actingAs($other, ['*']);

    get("/api/vehicle-loans/photos/pickups/{$pickup->id}")->assertForbidden();
});

it('lets the current approver change driver and unit', function () {
    $flow = ApprovalFlow::create([
        'code' => 'vehicle-loan.review-test',
        'name' => 'Review Test',
        'approvable_type' => VehicleLoan::class,
        'is_active' => true,
        'created_by' => $this->user->id,
    ]);

    $flowStep = ApprovalFlowStep::create([
        'approval_flow_id' => $flow->id,
        'step_order' => 1,
        'name' => 'Approve',
        'approver_type' => 'user',
        'approver_user_id' => $this->user->id,
        'required_action' => 'approve',
        'can_reject' => true,
    ]);

    $loan = VehicleLoan::factory()->create([
        'requester_id' => $this->user->id,
        'status' => 'in_progress',
    ]);

    $approval = ApprovalRequest::create([
        'approval_flow_id' => $flow->id,
        'approvable_type' => VehicleLoan::class,
        'approvable_id' => $loan->id,
        'current_step_order' => 1,
        'status' => 'in_progress',
        'submitted_by' => $this->user->id,
        'submitted_at' => now()->subHour(),
    ]);

    ApprovalRequestStep::create([
        'approval_request_id' => $approval->id,
        'approval_flow_step_id' => $flowStep->id,
        'step_order' => 1,
        'status' => 'pending',
    ]);

    $driver = VehicleDriver::factory()->create();
    $unit = VehicleUnit::factory()->create();

    postJson("/api/vehicle-loans/{$loan->id}/review-assignment", [
        'driver_id' => $driver->id,
        'vehicle_unit_id' => $unit->id,
        'review_notes' => 'Assigned by approver',
    ])->assertOk();

    $loan->refresh();

    expect($loan->driver_id)->toBe($driver->id)
        ->and($loan->vehicle_unit_id)->toBe($unit->id)
        ->and($loan->review_notes)->toBe('Assigned by approver');
});

it('exposes the notification inbox and marks notifications read', function () {
    $notification = DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => GenericAppNotification::class,
        'notifiable_type' => $this->user->getMorphClass(),
        'notifiable_id' => $this->user->id,
        'data' => [
            'event_key' => 'approval.request.approved',
            'title' => 'Disetujui',
            'body' => 'Permintaan disetujui',
            'target_url' => '/vehicle-loans/1',
        ],
        'dedupe_key' => 'test:1:' . $this->user->id . ':database',
    ]);

    getJson('/api/notifications')->assertOk()->assertJsonPath('data.0.id', $notification->id);
    getJson('/api/notifications?unread_only=1')->assertOk();
    getJson('/api/notifications/unread-count')->assertOk()->assertJsonPath('count', 1);

    postJson("/api/notifications/{$notification->id}/read")->assertOk();

    getJson('/api/notifications/unread-count')->assertOk()->assertJsonPath('count', 0);

    postJson('/api/notifications/999999/read')->assertStatus(404);

    $second = DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => GenericAppNotification::class,
        'notifiable_type' => $this->user->getMorphClass(),
        'notifiable_id' => $this->user->id,
        'data' => ['title' => 'Baru'],
        'dedupe_key' => 'test:2:' . $this->user->id . ':database',
    ]);
    expect($second->id)->not->toBeNull();

    postJson('/api/notifications/read-all')->assertOk();

    getJson('/api/notifications/unread-count')->assertOk()->assertJsonPath('count', 0);
});

it('hides mail reservation rows from the notification inbox', function () {
    DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => GenericAppNotification::class,
        'notifiable_type' => $this->user->getMorphClass(),
        'notifiable_id' => $this->user->id,
        'data' => ['event_key' => 'approval.request.created', 'channel' => 'mail'],
        'dedupe_key' => 'test:mail:' . $this->user->id,
    ]);

    getJson('/api/notifications')->assertOk()->assertJsonCount(0, 'data');
    getJson('/api/notifications/unread-count')->assertOk()->assertJsonPath('count', 0);
});

it('honors notification preferences', function () {
    $approver = $this->createTestUserWithPermissions(['my_approvals']);

    NotificationPreference::create([
        'user_id' => $approver->id,
        'channel' => 'database',
        'event_key' => 'approval.request.created',
        'enabled' => false,
    ]);

    $flow = ApprovalFlow::create([
        'code' => 'vehicle-loan.pref-test',
        'name' => 'Preference Test',
        'approvable_type' => VehicleLoan::class,
        'is_active' => true,
        'created_by' => $this->user->id,
    ]);

    ApprovalFlowStep::create([
        'approval_flow_id' => $flow->id,
        'step_order' => 1,
        'name' => 'Approve',
        'approver_type' => 'user',
        'approver_user_id' => $approver->id,
        'required_action' => 'approve',
        'can_reject' => true,
    ]);

    config()->set('vehicle_loan.approval_flow_code', 'vehicle-loan.pref-test');

    $loan = VehicleLoan::factory()->create([
        'requester_id' => $this->user->id,
        'status' => 'draft',
    ]);

    postJson("/api/vehicle-loans/{$loan->id}/submit")->assertOk();

    expect(DatabaseNotification::where('notifiable_id', $approver->id)->count())->toBe(0);
});

it('builds the generic notification mail', function () {
    $notification = new GenericAppNotification(
        'approval.request.approved',
        ['title' => 'Disetujui', 'body' => 'Permintaan disetujui'],
        '/vehicle-loans/1',
        'user@example.com',
        'Acme',
    );

    expect($notification->envelope()->subject)->toBe('Disetujui')
        ->and($notification->content()->with['body'])->toBe('Permintaan disetujui')
        ->and($notification->content()->with['companyName'])->toBe('Acme');
});

it('exports vehicle loans as a spreadsheet', function () {
    Storage::fake('public');

    postJson('/api/vehicle-loans/export', ['format' => 'xlsx'])
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);
});
