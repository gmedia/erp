<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use Illuminate\Database\Seeder;

class CoaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ==========================================
        // 2025 Data
        // ==========================================
        $fiscalYear2025 = FiscalYear::create([
            'name' => '2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'closed',
        ]);

        $coaVersion2025 = CoaVersion::create([
            'name' => 'COA 2025 Standard',
            'fiscal_year_id' => $fiscalYear2025->id,
            'status' => 'archived',
        ]);

        $accounts2025 = $this->getAccounts2025();
        $this->seedAccounts($accounts2025, $coaVersion2025->id);


        // ==========================================
        // 2026 Data (Different Structure)
        // ==========================================
        $fiscalYear2026 = FiscalYear::create([
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);

        $coaVersion2026 = CoaVersion::create([
            'name' => 'COA 2026 Enhanced',
            'fiscal_year_id' => $fiscalYear2026->id,
            'status' => 'active',
        ]);

        // Define 2026 accounts - structural changes:
        // 1. "Cash" (11100) split into "Cash in Bank" (11110) and "Petty Cash" (11120)
        // 2. Renamed "Operating Expense" (52000) to "General & Admin Expense" (52000)
        $accounts2026 = [
            // Assets
            [
                'code' => '10000', 'name' => 'Assets', 'type' => 'asset', 'normal_balance' => 'debit', 'level' => 1,
                'children' => [
                    ['code' => '11000', 'name' => 'Current Assets', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit', 'level' => 2, 'children' => [
                        // CHANGE 1: Split Cash
                        ['code' => '11110', 'name' => 'Cash in Banks', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit', 'level' => 3],
                        ['code' => '11120', 'name' => 'Petty Cash', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit', 'level' => 3],
                        
                        ['code' => '11200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit', 'level' => 3],
                        ['code' => '11300', 'name' => 'Inventory', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit', 'level' => 3],
                    ]],
                    ['code' => '12000', 'name' => 'Non-Current Assets', 'type' => 'asset', 'sub_type' => 'non_current_asset', 'normal_balance' => 'debit', 'level' => 2, 'children' => [
                         ['code' => '12100', 'name' => 'Equipment', 'type' => 'asset', 'sub_type' => 'non_current_asset', 'normal_balance' => 'debit', 'level' => 3],
                    ]],
                ]
            ],
            // Liabilities
            [
                'code' => '20000', 'name' => 'Liabilities', 'type' => 'liability', 'normal_balance' => 'credit', 'level' => 1,
                'children' => [
                    ['code' => '21000', 'name' => 'Current Liabilities', 'type' => 'liability', 'sub_type' => 'current_liability', 'normal_balance' => 'credit', 'level' => 2, 'children' => [
                        ['code' => '21100', 'name' => 'Accounts Payable', 'type' => 'liability', 'sub_type' => 'current_liability', 'normal_balance' => 'credit', 'level' => 3],
                    ]],
                ]
            ],
            // Equity
            [
                'code' => '30000', 'name' => 'Equity', 'type' => 'equity', 'normal_balance' => 'credit', 'level' => 1,
                'children' => [
                    ['code' => '31000', 'name' => 'Owner Capital', 'type' => 'equity', 'normal_balance' => 'credit', 'level' => 2],
                ]
            ],
            // Revenue
            [
                'code' => '40000', 'name' => 'Revenue', 'type' => 'revenue', 'normal_balance' => 'credit', 'level' => 1,
                'children' => [
                    ['code' => '41000', 'name' => 'Sales Revenue', 'type' => 'revenue', 'normal_balance' => 'credit', 'level' => 2],
                ]
            ],
            // Expense
            [
                'code' => '50000', 'name' => 'Expense', 'type' => 'expense', 'normal_balance' => 'debit', 'level' => 1,
                'children' => [
                    ['code' => '51000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'normal_balance' => 'debit', 'level' => 2],
                    // CHANGE 2: Rename
                    ['code' => '52000', 'name' => 'General & Admin Expense', 'type' => 'expense', 'normal_balance' => 'debit', 'level' => 2], 
                ]
            ],
        ];

        $this->seedAccounts($accounts2026, $coaVersion2026->id);
    }

    private function getAccounts2025(): array
    {
        return [
            // Assets
            [
                'code' => '10000', 'name' => 'Assets', 'type' => 'asset', 'normal_balance' => 'debit', 'level' => 1,
                'children' => [
                    ['code' => '11000', 'name' => 'Current Assets', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit', 'level' => 2, 'children' => [
                        ['code' => '11100', 'name' => 'Cash', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit', 'level' => 3],
                        ['code' => '11200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit', 'level' => 3],
                        ['code' => '11300', 'name' => 'Inventory', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit', 'level' => 3],
                    ]],
                    ['code' => '12000', 'name' => 'Non-Current Assets', 'type' => 'asset', 'sub_type' => 'non_current_asset', 'normal_balance' => 'debit', 'level' => 2, 'children' => [
                         ['code' => '12100', 'name' => 'Equipment', 'type' => 'asset', 'sub_type' => 'non_current_asset', 'normal_balance' => 'debit', 'level' => 3],
                    ]],
                ]
            ],
            // Liabilities
            [
                'code' => '20000', 'name' => 'Liabilities', 'type' => 'liability', 'normal_balance' => 'credit', 'level' => 1,
                'children' => [
                    ['code' => '21000', 'name' => 'Current Liabilities', 'type' => 'liability', 'sub_type' => 'current_liability', 'normal_balance' => 'credit', 'level' => 2, 'children' => [
                        ['code' => '21100', 'name' => 'Accounts Payable', 'type' => 'liability', 'sub_type' => 'current_liability', 'normal_balance' => 'credit', 'level' => 3],
                    ]],
                ]
            ],
            // Equity
            [
                'code' => '30000', 'name' => 'Equity', 'type' => 'equity', 'normal_balance' => 'credit', 'level' => 1,
                'children' => [
                    ['code' => '31000', 'name' => 'Owner Capital', 'type' => 'equity', 'normal_balance' => 'credit', 'level' => 2],
                ]
            ],
            // Revenue
            [
                'code' => '40000', 'name' => 'Revenue', 'type' => 'revenue', 'normal_balance' => 'credit', 'level' => 1,
                'children' => [
                    ['code' => '41000', 'name' => 'Sales Revenue', 'type' => 'revenue', 'normal_balance' => 'credit', 'level' => 2],
                ]
            ],
            // Expense
            [
                'code' => '50000', 'name' => 'Expense', 'type' => 'expense', 'normal_balance' => 'debit', 'level' => 1,
                'children' => [
                    ['code' => '51000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'normal_balance' => 'debit', 'level' => 2],
                    ['code' => '52000', 'name' => 'Operating Expense', 'type' => 'expense', 'normal_balance' => 'debit', 'level' => 2],
                ]
            ],
        ];
    }

    private function seedAccounts(array $accounts, int $coaVersionId, ?int $parentId = null)
    {
        foreach ($accounts as $accountData) {
            $children = $accountData['children'] ?? [];
            unset($accountData['children']);

            $accountData['coa_version_id'] = $coaVersionId;
            $accountData['parent_id'] = $parentId;
            
            $account = Account::create($accountData);

            if (!empty($children)) {
                $this->seedAccounts($children, $coaVersionId, $account->id);
            }
        }
    }
}
