<?php

namespace App\Http\Controllers;

use App\Actions\AccountingPosting\PostAssetDisposalJournalAction;
use App\Actions\AccountingPosting\ReverseDocumentJournalAction;
use App\Actions\AssetMovements\ExportAssetMovementsAction;
use App\Domain\AssetMovements\AssetMovementFilterService;
use App\Http\Requests\AssetMovements\ExportAssetMovementRequest;
use App\Http\Requests\AssetMovements\StoreAssetMovementRequest;
use App\Http\Requests\AssetMovements\UpdateAssetMovementRequest;
use App\Http\Resources\AssetMovements\AssetMovementResource;
use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\JournalEntry;
use App\Support\ExportStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class AssetMovementController extends Controller
{
    public function __construct(
        private PostAssetDisposalJournalAction $postAssetDisposalJournal,
        private ReverseDocumentJournalAction $reverseDisposalJournal,
    ) {}

    public function index(Request $request, AssetMovementFilterService $filterService): AnonymousResourceCollection
    {
        $query = AssetMovement::with([
            'asset',
            'fromBranch',
            'toBranch',
            'fromLocation',
            'toLocation',
            'fromDepartment',
            'toDepartment',
            'fromEmployee',
            'toEmployee',
            'createdBy',
        ]);

        if ($request->filled('search')) {
            $filterService->applySearch($query, $request->search, ['reference', 'notes', 'asset_name', 'asset_code']);
        } else {
            $filterService->applyAdvancedFilters($query, $request->all());
        }

        $filterService->applySorting(
            $query,
            $request->sort_by ?? 'moved_at',
            $request->sort_direction ?? 'desc',
            ['id', 'movement_type', 'moved_at', 'created_at']
        );

        $movements = $query->paginate($request->integer('per_page', 15));

        return AssetMovementResource::collection($movements);
    }

    public function store(StoreAssetMovementRequest $request): JsonResponse
    {
        $document = $this->storeDocument($request->file('document'));

        try {
            return DB::transaction(function () use ($request, $document) {
                $asset = Asset::whereKey($request->asset_id)->lockForUpdate()->firstOrFail();

                $this->assertMovementAllowed($asset, (string) $request->movement_type);

                $validated = $request->validated();
                unset($validated['document']);

                $movement = AssetMovement::create([
                    ...$validated,
                    'from_branch_id' => $asset->branch_id,
                    'from_location_id' => $asset->asset_location_id,
                    'from_department_id' => $asset->department_id,
                    'from_employee_id' => $asset->employee_id,
                    'created_by' => Auth::id(),
                    'document_path' => $document['path'] ?? null,
                    'document_name' => $document['name'] ?? null,
                    'status_before_disposal' => $request->movement_type === 'dispose' ? $asset->status : null,
                ]);

                // Update asset current state
                $asset->update([
                    'branch_id' => $request->to_branch_id ?? $asset->branch_id,
                    'asset_location_id' => $request->to_location_id ?? $asset->asset_location_id,
                    'department_id' => $request->to_department_id ?? $asset->department_id,
                    'employee_id' => $request->to_employee_id ?? $asset->employee_id,
                    'status' => $request->movement_type === 'dispose' ? 'disposed' : $asset->status,
                ]);

                // Pelepasan tanpa hasil penjualan dijurnal pada tanggal pelepasan (#442).
                if ($request->movement_type === 'dispose') {
                    $asset->refresh();
                    $this->postAssetDisposalJournal->execute(
                        $asset,
                        Carbon::parse($movement->moved_at)->toDateString(),
                        $movement->sale_amount !== null ? (float) $movement->sale_amount : null,
                        $movement->sale_account_id,
                    );
                }

                return response()->json([
                    'message' => 'Movement recorded successfully',
                    'data' => new AssetMovementResource($movement->load([
                        'fromBranch', 'toBranch', 'fromLocation', 'toLocation',
                        'fromDepartment', 'toDepartment', 'fromEmployee', 'toEmployee', 'createdBy',
                    ])),
                ], 201);
            });
        } catch (Throwable $e) {
            $this->deleteDocument($document['path'] ?? null);

            throw $e;
        }
    }

    public function show(AssetMovement $asset_movement): JsonResponse
    {
        return response()->json([
            'data' => new AssetMovementResource($asset_movement->load([
                'asset', 'fromBranch', 'toBranch', 'fromLocation', 'toLocation',
                'fromDepartment', 'toDepartment', 'fromEmployee', 'toEmployee', 'createdBy',
            ])),
        ]);
    }

    public function update(UpdateAssetMovementRequest $request, AssetMovement $asset_movement): JsonResponse
    {
        $validated = $request->validated();
        unset($validated['document']);

        // Aset dan tipe mutasi dikunci setelah dibuat: mengubahnya membuat status aset stale.
        $assetChanges = array_key_exists('asset_id', $validated)
            && (int) $validated['asset_id'] !== $asset_movement->asset_id;
        $typeChanges = array_key_exists('movement_type', $validated)
            && $validated['movement_type'] !== $asset_movement->movement_type;

        if ($typeChanges) {
            throw ValidationException::withMessages([
                'movement_type' => __('asset_movements.movement_type_locked'),
            ]);
        }

        if ($assetChanges) {
            throw ValidationException::withMessages([
                'asset_id' => __('asset_movements.asset_locked'),
            ]);
        }

        $oldDocumentPath = $asset_movement->document_path;
        $document = $this->storeDocument($request->file('document'));

        if ($document) {
            $validated['document_path'] = $document['path'];
            $validated['document_name'] = $document['name'];
        }

        try {
            $asset_movement->update($validated);
        } catch (Throwable $e) {
            $this->deleteDocument($document['path'] ?? null);

            throw $e;
        }

        // Hapus berkas lama hanya setelah perubahan tersimpan.
        if ($document && $oldDocumentPath) {
            $this->deleteDocument($oldDocumentPath);
        }

        return response()->json([
            'message' => 'Movement updated successfully',
            'data' => new AssetMovementResource($asset_movement->load([
                'asset', 'fromBranch', 'toBranch', 'fromLocation', 'toLocation',
                'fromDepartment', 'toDepartment', 'fromEmployee', 'toEmployee', 'createdBy',
            ])),
        ]);
    }

    public function destroy(AssetMovement $asset_movement): JsonResponse
    {
        return DB::transaction(function () use ($asset_movement) {
            $asset = $asset_movement->asset;

            // Find the latest movement for this asset
            $latestMovement = AssetMovement::where('asset_id', $asset->id)
                ->latest('moved_at')
                ->latest('id')
                ->first();

            // If we are deleting the latest movement, we should revert the asset state
            if ($latestMovement && $latestMovement->id === $asset_movement->id) {
                // Find the previous movement
                $previousMovement = AssetMovement::where('asset_id', $asset->id)
                    ->where('id', '!=', $asset_movement->id)
                    ->latest('moved_at')
                    ->latest('id')
                    ->first();

                if ($previousMovement) {
                    $asset->update([
                        'branch_id' => $previousMovement->to_branch_id,
                        'asset_location_id' => $previousMovement->to_location_id,
                        'department_id' => $previousMovement->to_department_id,
                        'employee_id' => $previousMovement->to_employee_id,
                    ]);
                } else {
                    // If no previous movement, revert to "initial" state if possible
                    // However, we don't have a record of the state before the first movement in the movements table
                    // unless we assume the 'from' fields of the first movement represent it.
                    $asset->update([
                        'branch_id' => $asset_movement->from_branch_id,
                        'asset_location_id' => $asset_movement->from_location_id,
                        'department_id' => $asset_movement->from_department_id,
                        'employee_id' => $asset_movement->from_employee_id,
                    ]);
                }
            }

            $isDisposal = $asset_movement->movement_type === 'dispose';
            $disposalJournalId = $isDisposal
                ? JournalEntry::where('reference', 'DISP-'.$asset->asset_code)
                    ->where('journal_type', 'system')
                    ->value('id')
                : null;

            $asset_movement->delete();

            // Membatalkan pelepasan (#442): jurnal DISP- dibalik, status/nilai aset dikembalikan.
            if ($isDisposal) {
                $stillDisposed = AssetMovement::where('asset_id', $asset->id)
                    ->where('movement_type', 'dispose')
                    ->exists();

                if (! $stillDisposed && $asset->status === 'disposed') {
                    if ($disposalJournalId !== null) {
                        $this->reverseDisposalJournal->execute(
                            $asset,
                            'REV-DISP-'.$asset->asset_code,
                            'Pelepasan aset',
                            now()->toDateString(),
                            null,
                            $disposalJournalId,
                        );
                    }

                    $asset->update([
                        'status' => $asset_movement->status_before_disposal ?? 'active',
                    ]);
                }
            }

            return response()->json([
                'message' => 'Movement deleted successfully',
            ]);
        });
    }

    public function export(ExportAssetMovementRequest $request, ExportAssetMovementsAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    /**
     * Aset yang sudah dilepas atau hilang hanya boleh menerima mutasi dispose yang sah (sekali).
     */
    private function assertMovementAllowed(Asset $asset, string $movementType): void
    {
        if ($movementType === 'dispose') {
            if ($asset->status === 'disposed') {
                throw ValidationException::withMessages([
                    'movement_type' => __('asset_movements.dispose_already_disposed'),
                ]);
            }

            return;
        }

        if (in_array($asset->status, ['disposed', 'lost'], true)) {
            throw ValidationException::withMessages([
                'asset_id' => __('asset_movements.movement_not_allowed_for_status', [
                    'type' => $movementType,
                    'status' => $asset->status,
                ]),
            ]);
        }
    }

    /**
     * @return array{path: string, name: string}|null
     */
    private function storeDocument(?UploadedFile $file): ?array
    {
        if (! $file) {
            return null;
        }

        return [
            'path' => $file->store('asset-movements/documents', ExportStorage::diskName()),
            'name' => $file->getClientOriginalName(),
        ];
    }

    private function deleteDocument(?string $path): void
    {
        if ($path) {
            ExportStorage::disk()->delete($path);
        }
    }
}
