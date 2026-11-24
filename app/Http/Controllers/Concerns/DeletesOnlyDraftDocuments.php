<?php

namespace App\Http\Controllers\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

/**
 * Issue #375: DELETE hanya untuk dokumen draft.
 *
 * Dokumen yang sudah terposting (punya jurnal, stok, atau alokasi) tidak boleh
 * hilang: hapus baris saja akan meninggalkan jurnal/stok yatim. Pembatalan
 * dokumen terposting dilakukan lewat transisi status, bukan DELETE.
 */
trait DeletesOnlyDraftDocuments
{
    /**
     * @param  (Closure(): void)|null  $delete  Logika hapus khusus modul (mis. refresh alokasi);
     *                                          default: $model->delete().
     */
    protected function destroyDraftDocument(Model $model, ?Closure $delete = null): JsonResponse
    {
        if ($model->getAttribute('status') !== 'draft') {
            return response()->json([
                'message' => __('Only draft documents can be deleted. Cancel the document instead.'),
            ], 422);
        }

        if ($delete !== null) {
            $delete();
        } else {
            $model->delete();
        }

        return response()->json(null, 204);
    }
}
