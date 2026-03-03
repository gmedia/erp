/**
 * Shared model type options used across Approval Flows and Pipelines.
 * Each entry maps a human-readable label to its fully-qualified Eloquent model class.
 */
export const APPROVABLE_TYPE_OPTIONS = [
    { value: 'App\\Models\\PurchaseRequest', label: 'Purchase Request' },
    { value: 'App\\Models\\PurchaseOrder', label: 'Purchase Order' },
    { value: 'App\\Models\\JournalEntry', label: 'Journal Entry' },
    { value: 'App\\Models\\Asset', label: 'Asset' },
    { value: 'App\\Models\\AssetMovement', label: 'Asset Movement' },
    { value: 'App\\Models\\AssetMaintenance', label: 'Asset Maintenance' },
    { value: 'App\\Models\\AssetStocktake', label: 'Asset Stocktake' },
] as const;
