/**
 * Penentuan "terkunci" untuk dokumen terposting (Issue #375).
 *
 * Aturan sama dengan App\Http\Requests\Concerns\LocksPostedDocumentValues:
 * dokumen terkunci bila sudah punya journal_entry_id dan statusnya bukan draft.
 * Field nilai dan koleksi item/alokasi terkunci; field lain tetap bisa diubah.
 */

export interface PostedDocumentLockSource {
    status: string;
    journal_entry_id: number | null | undefined;
}

export const isPostedDocumentLocked = (
    document: PostedDocumentLockSource | null | undefined,
): boolean => {
    if (!document) {
        return false;
    }

    if (
        document.journal_entry_id === null ||
        document.journal_entry_id === undefined
    ) {
        return false;
    }

    return document.status !== 'draft';
};

/** Field nilai dokumen yang terkunci ketika dokumen terposting. */
export const POSTED_LOCKED_VALUE_FIELDS = [
    'total_amount',
    'subtotal',
    'grand_total',
    'amount_paid',
    'amount_received',
    'amount_due',
] as const;

/** Field koleksi (items/allocations) yang terkunci ketika dokumen terposting. */
export const POSTED_LOCKED_COLLECTION_FIELDS = [
    'items',
    'allocations',
] as const;
