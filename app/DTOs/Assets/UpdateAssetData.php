<?php

namespace App\DTOs\Assets;

readonly class UpdateAssetData
{
    public function __construct(
        public ?string $asset_code = null,
        public ?string $name = null,
        public ?int $asset_category_id = null,
        public ?int $asset_model_id = null,
        public ?string $serial_number = null,
        public ?string $barcode = null,
        public ?int $branch_id = null,
        public ?int $asset_location_id = null,
        public ?int $department_id = null,
        public ?int $employee_id = null,
        public ?int $supplier_id = null,
        public ?string $purchase_date = null,
        public ?string $purchase_cost = null,
        public ?string $currency = null,
        public ?string $warranty_end_date = null,
        public ?string $status = null,
        public ?string $condition = null,
        public ?string $notes = null,
        public ?string $depreciation_method = null,
        public ?string $depreciation_start_date = null,
        public ?int $useful_life_months = null,
        public ?string $salvage_value = null,
        public ?int $depreciation_expense_account_id = null,
        public ?int $accumulated_depr_account_id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            asset_code: $data['asset_code'] ?? null,
            name: $data['name'] ?? null,
            asset_category_id: $data['asset_category_id'] ?? null,
            asset_model_id: $data['asset_model_id'] ?? null,
            serial_number: $data['serial_number'] ?? null,
            barcode: $data['barcode'] ?? null,
            branch_id: $data['branch_id'] ?? null,
            asset_location_id: $data['asset_location_id'] ?? null,
            department_id: $data['department_id'] ?? null,
            employee_id: $data['employee_id'] ?? null,
            supplier_id: $data['supplier_id'] ?? null,
            purchase_date: $data['purchase_date'] ?? null,
            purchase_cost: isset($data['purchase_cost']) ? (string) $data['purchase_cost'] : null,
            currency: $data['currency'] ?? null,
            warranty_end_date: $data['warranty_end_date'] ?? null,
            status: $data['status'] ?? null,
            condition: $data['condition'] ?? null,
            notes: $data['notes'] ?? null,
            depreciation_method: $data['depreciation_method'] ?? null,
            depreciation_start_date: $data['depreciation_start_date'] ?? null,
            useful_life_months: $data['useful_life_months'] ?? null,
            salvage_value: isset($data['salvage_value']) ? (string) $data['salvage_value'] : null,
            depreciation_expense_account_id: $data['depreciation_expense_account_id'] ?? null,
            accumulated_depr_account_id: $data['accumulated_depr_account_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [];
        
        foreach (get_object_vars($this) as $key => $value) {
            if ($value !== null) {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
