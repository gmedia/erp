<?php

use App\Models\VehicleLoan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class)->group('vehicle-loans');

beforeEach(function () {
    Mail::fake();
    Storage::fake('local');
    config()->set('vehicle_loan.photo_disk', 'local');
});

it('deletes photos older than the retention window', function () {
    config()->set('vehicle_loan.photo_retention_days', 10);
    $disk = Storage::disk('local');

    $oldPath = 'vehicle-loans/pickups/old.jpg';
    $newPath = 'vehicle-loans/pickups/new.jpg';
    $disk->put($oldPath, 'old');
    $disk->put($newPath, 'new');
    touch($disk->path($oldPath), now()->subDays(11)->getTimestamp());

    artisan('vehicle-loans:cleanup')
        ->expectsOutputToContain('Photos deleted: 1')
        ->assertSuccessful();

    expect($disk->exists($oldPath))->toBeFalse()
        ->and($disk->exists($newPath))->toBeTrue();
});

it('does not delete anything in dry-run mode', function () {
    config()->set('vehicle_loan.photo_retention_days', 10);
    $disk = Storage::disk('local');
    $path = 'vehicle-loans/returns/old.jpg';
    $disk->put($path, 'old');
    touch($disk->path($path), now()->subDays(30)->getTimestamp());

    artisan('vehicle-loans:cleanup --dry-run')
        ->expectsOutputToContain('(dry run)')
        ->assertSuccessful();

    expect($disk->exists($path))->toBeTrue();
});

it('cancels stale draft loans', function () {
    config()->set('vehicle_loan.draft_expiry_days', 7);

    $stale = VehicleLoan::factory()->create(['status' => 'draft']);
    $fresh = VehicleLoan::factory()->create(['status' => 'draft']);
    VehicleLoan::whereKey($stale->id)->update(['created_at' => now()->subDays(8)]);

    artisan('vehicle-loans:cleanup')
        ->expectsOutputToContain('Drafts expired: 1')
        ->assertSuccessful();

    expect($stale->fresh()->status)->toBe('cancelled')
        ->and($fresh->fresh()->status)->toBe('draft');
});

it('notifies requester and flags overdue loans', function () {
    $loan = VehicleLoan::factory()->create([
        'status' => 'pickup_done',
        'end_at' => now()->subDay(),
        'is_overdue' => false,
    ]);

    artisan('vehicle-loans:notify-overdue')
        ->expectsOutputToContain('Overdue loans notified: 1')
        ->assertSuccessful();

    expect($loan->fresh()->is_overdue)->toBeTrue();

    $notification = DatabaseNotification::where('notifiable_id', $loan->requester_id)->first();

    expect($notification)->not->toBeNull()
        ->and($notification->data['event_key'])->toBe('vehicle_loan.return.due');
});

it('does not re-notify already overdue loans', function () {
    VehicleLoan::factory()->create([
        'status' => 'pickup_done',
        'end_at' => now()->subDay(),
        'is_overdue' => true,
    ]);

    artisan('vehicle-loans:notify-overdue')
        ->expectsOutputToContain('Overdue loans notified: 0')
        ->assertSuccessful();
});

it('does not write in overdue dry-run mode', function () {
    $loan = VehicleLoan::factory()->create([
        'status' => 'pickup_done',
        'end_at' => now()->subDay(),
        'is_overdue' => false,
    ]);

    artisan('vehicle-loans:notify-overdue --dry-run')
        ->expectsOutputToContain('Overdue loans notified: 1 (dry run)')
        ->assertSuccessful();

    expect($loan->fresh()->is_overdue)->toBeFalse()
        ->and(DatabaseNotification::count())->toBe(0);
});
