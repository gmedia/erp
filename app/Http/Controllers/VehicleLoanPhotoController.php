<?php

namespace App\Http\Controllers;

use App\Models\VehicleLoanActivityLog;
use App\Models\VehicleLoanPickup;
use App\Models\VehicleLoanReturn;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VehicleLoanPhotoController extends Controller
{
    public function pickup(VehicleLoanPickup $vehicleLoanPickup): Response
    {
        $loan = $vehicleLoanPickup->loan;

        if (! $this->canView($loan->requester_id, $loan->approved_by, $vehicleLoanPickup->security_user_id)) {
            abort(403);
        }

        $this->logView($vehicleLoanPickup->vehicle_loan_id, 'pickup_photo_viewed');

        return $this->response($vehicleLoanPickup->selfie_photo_path, $vehicleLoanPickup->selfie_photo_name);
    }

    public function handover(VehicleLoanReturn $vehicleLoanReturn): Response
    {
        $loan = $vehicleLoanReturn->loan;
        $pickup = $vehicleLoanReturn->pickup;

        if (! $this->canView($loan->requester_id, $loan->approved_by, $pickup->security_user_id)) {
            abort(403);
        }

        $this->logView($vehicleLoanReturn->vehicle_loan_id, 'handover_photo_viewed');

        return $this->response($vehicleLoanReturn->handover_photo_path, $vehicleLoanReturn->handover_photo_name);
    }

    public function condition(VehicleLoanReturn $vehicleLoanReturn): Response
    {
        $loan = $vehicleLoanReturn->loan;
        $pickup = $vehicleLoanReturn->pickup;

        if (! $this->canView($loan->requester_id, $loan->approved_by, $pickup->security_user_id)) {
            abort(403);
        }

        $this->logView($vehicleLoanReturn->vehicle_loan_id, 'condition_photo_viewed');

        return $this->response($vehicleLoanReturn->condition_photo_path, $vehicleLoanReturn->condition_photo_name);
    }

    private function logView(int $loanId, string $event): void
    {
        VehicleLoanActivityLog::create([
            'vehicle_loan_id' => $loanId,
            'event' => $event,
            'actor_user_id' => Auth::id(),
        ]);
    }

    private function canView(int $requesterId, ?int $approverId, ?int $securityId): bool
    {
        $userId = Auth::id();

        return $userId === $requesterId
            || $userId === $approverId
            || $userId === $securityId;
    }

    private function response(string $path, string $name): Response
    {
        $disk = Storage::disk(config('vehicle_loan.photo_disk', 'local'));

        if (! $disk->exists($path)) {
            abort(404);
        }

        return response($disk->get($path), 200, [
            'Content-Type' => $disk->mimeType($path),
            'Content-Disposition' => 'inline; filename="' . $name . '"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
