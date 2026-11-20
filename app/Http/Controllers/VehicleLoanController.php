<?php

namespace App\Http\Controllers;

use App\Actions\VehicleLoans\ExportVehicleLoansAction;
use App\Actions\VehicleLoans\GenerateVehicleLoanCodeAction;
use App\Actions\VehicleLoans\HandoverVehicleLoanKeyAction;
use App\Actions\VehicleLoans\ReviewVehicleLoanAssignmentAction;
use App\Actions\VehicleLoans\StoreVehicleLoanPhotoAction;
use App\Actions\VehicleLoans\StoreVehicleLoanPickupAction;
use App\Actions\VehicleLoans\StoreVehicleLoanReturnAction;
use App\Actions\VehicleLoans\SubmitVehicleLoanAction;
use App\Http\Controllers\Concerns\ResolvesBranchScope;
use App\Http\Requests\VehicleLoans\ExportVehicleLoansRequest;
use App\Http\Requests\VehicleLoans\HandoverVehicleLoanKeyRequest;
use App\Http\Requests\VehicleLoans\IndexVehicleLoanRequest;
use App\Http\Requests\VehicleLoans\ReviewVehicleLoanAssignmentRequest;
use App\Http\Requests\VehicleLoans\StoreVehicleLoanPickupRequest;
use App\Http\Requests\VehicleLoans\StoreVehicleLoanRequest;
use App\Http\Requests\VehicleLoans\StoreVehicleLoanReturnRequest;
use App\Http\Requests\VehicleLoans\UpdateVehicleLoanRequest;
use App\Http\Resources\VehicleLoans\VehicleLoanResource;
use App\Models\ApprovalRequest;
use App\Models\VehicleDriver;
use App\Models\VehicleLoan;
use App\Models\VehicleLoanPickup;
use App\Models\VehicleUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class VehicleLoanController extends Controller
{
    use ResolvesBranchScope;

    public function index(IndexVehicleLoanRequest $request): AnonymousResourceCollection
    {
        $query = VehicleLoan::query()
            ->with(['requester', 'department', 'driver.employee', 'vehicleUnit.asset', 'pickup', 'returns'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when(
                $request->filled('date_from'),
                fn ($q) => $q->whereDate('start_at', '>=', $request->date('date_from')),
            )
            ->when(
                $request->filled('date_to'),
                fn ($q) => $q->whereDate('end_at', '<=', $request->date('date_to')),
            )
            ->when($request->boolean('mine'), fn ($q) => $q->where('requester_id', Auth::id()))
            ->when(
                $this->resolveBranchFromRequest($request) !== null,
                fn ($q) => $q->where('branch_id', $this->resolveBranchFromRequest($request)),
            )
            ->orderByDesc('id');

        return VehicleLoanResource::collection($query->paginate($request->integer('per_page', 15)));
    }

    public function store(StoreVehicleLoanRequest $request, GenerateVehicleLoanCodeAction $generateCode): JsonResponse
    {
        $loan = DB::transaction(fn () => VehicleLoan::create([
            ...$request->validated(),
            'code' => $generateCode->execute(now()),
            'requester_id' => Auth::id(),
            'department_id' => Auth::user()?->employee?->currentEmployment?->department_id,
            'branch_id' => Auth::user()?->employee?->currentEmployment?->branch_id,
            'company_id' => Auth::user()?->employee?->currentEmployment?->company_id,
            'status' => 'draft',
        ]));

        return (new VehicleLoanResource($loan))->response()->setStatusCode(201);
    }

    public function show(VehicleLoan $vehicleLoan): JsonResponse
    {
        return (new VehicleLoanResource($vehicleLoan->load([
            'requester', 'department', 'branch', 'company', 'driver.employee', 'vehicleUnit.asset',
            'pickup', 'returns', 'approvalRequests.steps.flowStep.user', 'activityLogs.actor',
        ])))->response();
    }

    public function update(UpdateVehicleLoanRequest $request, VehicleLoan $vehicleLoan): JsonResponse
    {
        if ($vehicleLoan->status !== 'draft') {
            abort(422, 'Only draft loans can be updated.');
        }

        $vehicleLoan->update($request->validated());

        return (new VehicleLoanResource($vehicleLoan))->response();
    }

    public function destroy(VehicleLoan $vehicleLoan): JsonResponse
    {
        if (! in_array($vehicleLoan->status, ['draft', 'rejected'], true)) {
            abort(422, 'Only draft or rejected loans can be cancelled.');
        }

        $vehicleLoan->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Loan request cancelled.']);
    }

    public function assignmentOptions(): JsonResponse
    {
        return response()->json([
            'drivers' => VehicleDriver::query()
                ->where('is_active', true)
                ->with('employee')
                ->orderBy('id')
                ->get()
                ->map(fn (VehicleDriver $driver): array => [
                    'id' => $driver->id,
                    'name' => $driver->employee->name,
                    'license_expiry' => $driver->license_expiry?->toDateString(),
                ]),
            'units' => VehicleUnit::query()
                ->where('is_active', true)
                ->with('asset')
                ->orderBy('id')
                ->get()
                ->map(fn (VehicleUnit $unit): array => [
                    'id' => $unit->id,
                    'plate_number' => $unit->plate_number,
                    'vehicle_type' => $unit->vehicle_type,
                ]),
        ]);
    }

    public function mobileConfig(): JsonResponse
    {
        return response()->json([
            'public_base_url' => config('vehicle_loan.public_base_url'),
            'qr_paths' => config('vehicle_loan.qr_paths'),
        ]);
    }

    public function export(
        ExportVehicleLoansRequest $request,
        ExportVehicleLoansAction $action,
    ): JsonResponse {
        return $action->execute($request);
    }

    public function submit(
        VehicleLoan $vehicleLoan,
        SubmitVehicleLoanAction $action,
    ): JsonResponse {
        $this->authorizeRequester($vehicleLoan);

        $this->businessRule(fn () => $action->execute($vehicleLoan));

        return (new VehicleLoanResource($vehicleLoan->fresh()))->response();
    }

    public function reviewAssignment(
        ReviewVehicleLoanAssignmentRequest $request,
        VehicleLoan $vehicleLoan,
        ReviewVehicleLoanAssignmentAction $action,
    ): JsonResponse {
        /** @var ApprovalRequest|null $approval */
        $approval = $vehicleLoan->approvalRequests()->latest()->first();

        if (! $approval) {
            abort(404, 'Approval request not found.');
        }

        $action->execute($vehicleLoan, $approval, $request->validated());

        return (new VehicleLoanResource($vehicleLoan->fresh()))->response();
    }

    public function my(): AnonymousResourceCollection
    {
        $loans = VehicleLoan::where('requester_id', Auth::id())
            ->with(['requester', 'driver.employee', 'vehicleUnit.asset', 'pickup', 'returns'])
            ->orderByDesc('id')
            ->paginate(15);

        return VehicleLoanResource::collection($loans);
    }

    public function securityQueue(Request $request): AnonymousResourceCollection
    {
        $branchId = $this->resolveBranchFromRequest($request);

        $query = VehicleLoan::whereIn('status', ['approved', 'pickup_done'])
            ->with([
                'requester',
                'department',
                'driver.employee',
                'vehicleUnit.asset',
                'pickup',
                'approvalRequests.steps.flowStep.user',
            ]);

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        $loans = $query->orderBy('start_at')->paginate(20);

        return VehicleLoanResource::collection($loans);
    }

    public function storePickup(
        StoreVehicleLoanPickupRequest $request,
        VehicleLoan $vehicleLoan,
        StoreVehicleLoanPickupAction $action,
        StoreVehicleLoanPhotoAction $photos,
    ): JsonResponse {
        $this->authorizeRequester($vehicleLoan);

        $photo = $photos->execute($request->file('selfie_photo'), 'vehicle-loans/pickups');

        $pickup = $this->businessRule(fn () => $action->execute($vehicleLoan, [
            ...$request->validated(),
            'selfie_photo_path' => $photo['path'],
            'selfie_photo_name' => $photo['name'],
        ]));

        return response()->json($pickup, 201);
    }

    public function handoverKey(
        HandoverVehicleLoanKeyRequest $request,
        VehicleLoanPickup $vehicleLoanPickup,
        HandoverVehicleLoanKeyAction $action,
    ): JsonResponse {
        $this->businessRule(fn () => $action->execute($vehicleLoanPickup));

        return response()->json($vehicleLoanPickup->fresh());
    }

    public function storeReturn(
        StoreVehicleLoanReturnRequest $request,
        VehicleLoanPickup $vehicleLoanPickup,
        StoreVehicleLoanReturnAction $action,
        StoreVehicleLoanPhotoAction $photos,
    ): JsonResponse {
        $this->authorizeRequester($vehicleLoanPickup->loan);

        $handover = $photos->execute($request->file('handover_photo'), 'vehicle-loans/returns');
        $condition = $photos->execute($request->file('condition_photo'), 'vehicle-loans/returns');

        $return = $this->businessRule(fn () => $action->execute($vehicleLoanPickup, [
            ...$request->validated(),
            'handover_photo_path' => $handover['path'],
            'handover_photo_name' => $handover['name'],
            'condition_photo_path' => $condition['path'],
            'condition_photo_name' => $condition['name'],
        ]));

        return response()->json($return, 201);
    }

    /**
     * Translate business-rule violations into 422 responses.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function businessRule(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['business_rule' => $exception->getMessage()]);
        }
    }

    private function authorizeRequester(VehicleLoan $loan): void
    {
        if ($loan->requester_id !== Auth::id()) {
            abort(403, 'Only the requester can perform this action.');
        }
    }
}
