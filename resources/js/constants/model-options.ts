/**
 * Shared model type options used across Approval Flows and Pipelines.
 * Each entry maps a human-readable label to its fully-qualified Eloquent model class.
 */
export const APPROVABLE_TYPE_OPTIONS = [
    {
        value: String.raw`App\Models\PurchaseRequest`,
        label: 'Purchase Request',
    },
    {
        value: String.raw`App\Models\PurchaseOrder`,
        label: 'Purchase Order',
    },
    { value: String.raw`App\Models\JournalEntry`, label: 'Journal Entry' },
    { value: String.raw`App\Models\Asset`, label: 'Asset' },
    { value: String.raw`App\Models\AssetMovement`, label: 'Asset Movement' },
    {
        value: String.raw`App\Models\AssetMaintenance`,
        label: 'Asset Maintenance',
    },
    {
        value: String.raw`App\Models\AssetStocktake`,
        label: 'Asset Stocktake',
    },
] as const;
