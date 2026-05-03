<?php

namespace App\Http\Controllers;

use App\Actions\ApPayments\ExportApPaymentsAction;
use App\Actions\ApPayments\IndexApPaymentsAction;
use App\Actions\ApPayments\SyncApPaymentAllocationsAction;
use App\DTOs\ApPayments\UpdateApPaymentData;
use App\Http\Controllers\Concerns\LoadsResourceRelations;
use App\Http\Controllers\Concerns\StoresItemsInTransaction;
use App\Http\Requests\ApPayments\ExportApPaymentRequest;
use App\Http\Requests\ApPayments\IndexApPaymentRequest;
use App\Http\Requests\ApPayments\StoreApPaymentRequest;
use App\Http\Requests\ApPayments\UpdateApPaymentRequest;
use App\Http\Resources\ApPayments\ApPaymentCollection;
use App\Http\Resources\ApPayments\ApPaymentResource;
use App\Models\ApPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApPaymentController extends Controller
{
    use LoadsResourceRelations;
    use StoresItemsInTransaction;

    public function index(IndexApPaymentRequest $request, IndexApPaymentsAction $action): JsonResponse
    {
        $apPayments = $action->execute($request);

        return (new ApPaymentCollection($apPayments))->response();
    }

    public function store(StoreApPaymentRequest $request, SyncApPaymentAllocationsAction $syncAllocations): JsonResponse
    {
        $validated = $request->validated();
        $allocations = $validated['allocations'];
        unset($validated['allocations']);

        $validated['created_by'] = Auth::id();
        $validated['total_allocated'] = 0;
        $validated['total_unallocated'] = $validated['total_amount'];

        $apPayment = $this->storeWithSyncedItems(
            attributes: $validated,
            items: $allocations,
            creator: static fn (array $attributes): ApPayment => ApPayment::create($attributes),
            assignDocumentNumber: function (ApPayment $apPayment): void {
                $this->assignSequentialDocumentNumber($apPayment, 'payment_number', 'PAY');
            },
            syncItems: function (ApPayment $apPayment, array $allocations) use ($syncAllocations): void {
                $syncAllocations->execute($apPayment, $allocations);
            },
        );

        return (new ApPaymentResource($this->loadResourceRelations($apPayment)))->response()->setStatusCode(201);
    }

    public function show(ApPayment $apPayment): JsonResponse
    {
        return (new ApPaymentResource($this->loadResourceRelations($apPayment)))->response();
    }

    public function update(
        UpdateApPaymentRequest $request,
        ApPayment $apPayment,
        SyncApPaymentAllocationsAction $syncAllocations
    ): JsonResponse {
        $validated = $request->validated();
        $allocations = $validated['allocations'] ?? null;
        unset($validated['allocations']);

        if (($validated['status'] ?? null) === 'confirmed' && $apPayment->confirmed_at === null) {
            $validated['confirmed_by'] = Auth::id();
            $validated['confirmed_at'] = now()->toIso8601String();
        }

        if (($validated['status'] ?? null) === 'pending_approval') {
            $validated['approved_by'] = null;
            $validated['approved_at'] = null;
        }

        $this->updateWithSyncedItems(
            model: $apPayment,
            attributes: $validated,
            items: $allocations,
            payloadResolver: static fn (array $attributes): array => UpdateApPaymentData::fromArray($attributes)->toArray(),
            syncItems: function (ApPayment $apPayment, array $allocations) use ($syncAllocations): void {
                $syncAllocations->execute($apPayment, $allocations);
            },
        );

        return (new ApPaymentResource($this->loadResourceRelations($apPayment)))->response();
    }

    public function destroy(ApPayment $apPayment): JsonResponse
    {
        DB::transaction(function () use ($apPayment): void {
            /** @var \App\Models\ApPaymentAllocation $allocation */
            foreach ($apPayment->allocations()->with('supplierBill')->get() as $allocation) {
                $bill = $allocation->supplierBill;

                $newAmountPaid = (float) $bill->amount_paid - (float) $allocation->allocated_amount;
                $bill->update([
                    'amount_paid' => (string) max(0, $newAmountPaid),
                    'amount_due' => (string) ((float) $bill->grand_total - max(0, $newAmountPaid)),
                ]);
                $bill->updatePaymentStatus();
            }
            $apPayment->delete();
        });

        return response()->json(null, 204);
    }

    public function export(ExportApPaymentRequest $request, ExportApPaymentsAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    protected function resourceRelations(): array
    {
        return [
            'supplier',
            'branch',
            'fiscalYear',
            'bankAccount',
            'approver',
            'creator',
            'confirmer',
            'allocations.supplierBill',
        ];
    }
}
