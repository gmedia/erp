<?php

namespace Database\Seeders;

use App\Actions\AccountingPosting\PostOpeningCapitalJournalAction;
use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class CoaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ==========================================
        // 2025 Data (Tahun Lalu - Closed)
        // ==========================================
        // Idempoten (#357): db:seed kedua tidak menambah FY/COA/akun, dan COA
        // buatan pengguna dengan nama lain tidak tersentuh.
        $fiscalYear2025 = FiscalYear::firstOrCreate(
            ['name' => '2025'],
            [
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'status' => 'closed',
            ],
        );

        $coaVersion2025 = CoaVersion::firstOrCreate(
            ['name' => 'COA 2025 Standard'],
            [
                'fiscal_year_id' => $fiscalYear2025->id,
                'status' => 'archived',
            ],
        );

        if (! Account::where('coa_version_id', $coaVersion2025->id)->exists()) {
            $accounts2025 = $this->getAccounts2025();
            $this->seedAccountsRecursive($accounts2025, $coaVersion2025->id);
        }

        // ==========================================
        // 2026 Data (Tahun Berjalan - Active)
        // ==========================================
        $fiscalYear2026 = FiscalYear::firstOrCreate(
            ['name' => '2026'],
            [
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 'open',
            ],
        );

        $coaVersion2026 = CoaVersion::firstOrCreate(
            ['name' => 'COA 2026 Enhanced'],
            [
                'fiscal_year_id' => $fiscalYear2026->id,
                'status' => 'active',
            ],
        );

        // 2026 structure with changes:
        // 1. "Cash" (11100) split into "Cash in Bank" (11110) and "Petty Cash" (11120)
        // 2. Renamed "Operating Expense" (52000) to "General & Admin Expense" (52000)
        if (! Account::where('coa_version_id', $coaVersion2026->id)->exists()) {
            $accounts2026 = $this->getAccounts2026();
            $this->seedAccountsRecursive($accounts2026, $coaVersion2026->id);
        }

        // Akun laba/rugi pelepasan aset (#442). Ditambahkan idempoten agar
        // COA yang sudah ada ikut memperoleh akun ini tanpa diseed ulang.
        $this->ensureAssetDisposalAccounts($coaVersion2026->id);

        // Fetch account maps from database for reliability
        $accountMap2025 = Account::where('coa_version_id', $coaVersion2025->id)
            ->pluck('id', 'code')
            ->toArray();
        $accountMap2026 = Account::where('coa_version_id', $coaVersion2026->id)
            ->pluck('id', 'code')
            ->toArray();

        // ==========================================
        // Account Mappings (untuk laporan komparatif)
        // ==========================================
        if (! AccountMapping::query()->whereIn('target_account_id', array_values($accountMap2026))->exists()) {
            $this->createAccountMappings($accountMap2025, $accountMap2026);
        }

        // ==========================================
        // Saldo awal modal (nilai tetap, diposting lewat action aplikasi).
        // Transaksi dokumen demo diposting oleh seeder dokumen masing-masing.
        // ==========================================
        $this->seedOpeningCapital($fiscalYear2026);
    }

    /**
     * Seed accounts recursively and return a map of code => account id
     */
    private function seedAccountsRecursive(array $accounts, int $coaVersionId, ?int $parentId = null): void
    {
        foreach ($accounts as $accountData) {
            $children = $accountData['children'] ?? [];
            unset($accountData['children']);

            $accountData['coa_version_id'] = $coaVersionId;
            $accountData['parent_id'] = $parentId;

            $account = Account::create($accountData);
            if (! empty($children)) {
                $this->seedAccountsRecursive($children, $coaVersionId, $account->id);
            }
        }
    }

    /**
     * Create account mappings between versions
     */
    private function createAccountMappings(array $map2025, array $map2026): void
    {
        // Mapping 1: Split - Cash (11100) split into Cash in Bank (11110) and Petty Cash (11120)
        if (isset($map2025['11100']) && isset($map2026['11110'])) {
            AccountMapping::create([
                'source_account_id' => $map2025['11100'],
                'target_account_id' => $map2026['11110'],
                'type' => 'split',
                'notes' => 'Cash split into Cash in Bank (primary)',
            ]);
        }

        if (isset($map2025['11100']) && isset($map2026['11120'])) {
            AccountMapping::create([
                'source_account_id' => $map2025['11100'],
                'target_account_id' => $map2026['11120'],
                'type' => 'split',
                'notes' => 'Cash split into Petty Cash (secondary)',
            ]);
        }

        // Mapping 2: Rename - Operating Expense (52000) to General & Admin Expense (52000)
        if (isset($map2025['52000']) && isset($map2026['52000'])) {
            AccountMapping::create([
                'source_account_id' => $map2025['52000'],
                'target_account_id' => $map2026['52000'],
                'type' => 'rename',
                'notes' => 'Operating Expense renamed to General & Admin Expense',
            ]);
        }
    }

    /**
     * Akun laba dan rugi pelepasan aset (#442), dipasang sebagai anak grup
     * Revenue (laba, kredit) dan Expense (rugi, debit) lalu diset ke Setting.
     */
    private function ensureAssetDisposalAccounts(int $coaVersionId): void
    {
        $gain = $this->firstOrCreateAccount($coaVersionId, '40000', [
            'code' => '41800',
            'name' => 'Gain on Asset Disposal',
            'type' => 'revenue',
            'normal_balance' => 'credit',
            'level' => 2,
        ]);

        $loss = $this->firstOrCreateAccount($coaVersionId, '50000', [
            'code' => '53100',
            'name' => 'Loss on Asset Disposal',
            'type' => 'expense',
            'normal_balance' => 'debit',
            'level' => 2,
        ]);

        Setting::updateOrCreate(
            ['key' => 'asset_disposal_gain_account_id'],
            ['group' => 'accounting', 'value' => (string) $gain->id, 'type' => 'string'],
        );
        Setting::updateOrCreate(
            ['key' => 'asset_disposal_loss_account_id'],
            ['group' => 'accounting', 'value' => (string) $loss->id, 'type' => 'string'],
        );
    }

    private function firstOrCreateAccount(int $coaVersionId, string $parentCode, array $attributes): Account
    {
        $parent = Account::query()
            ->where('coa_version_id', $coaVersionId)
            ->where('code', $parentCode)
            ->firstOrFail();

        return Account::firstOrCreate(
            ['coa_version_id' => $coaVersionId, 'code' => $attributes['code']],
            [...$attributes, 'coa_version_id' => $coaVersionId, 'parent_id' => $parent->id],
        );
    }

    /**
     * Saldo awal modal disetor Rp 500.000.000 ke bank (nilai tetap).
     * Idempoten lewat PostOpeningCapitalJournalAction.
     */
    private function seedOpeningCapital(FiscalYear $fiscalYear): void
    {
        app(PostOpeningCapitalJournalAction::class)->execute(
            $fiscalYear->start_date->format('Y-m-d'),
            500000000,
            'OPEN-CAP-' . $fiscalYear->name,
            'Saldo awal modal disetor pemilik',
        );
    }

    private function getAccounts2025(): array
    {
        return [
            // Assets
            [
                'code' => '10000',
                'name' => 'Assets',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'level' => 1,
                'children' => [
                    [
                        'code' => '11000',
                        'name' => 'Current Assets',
                        'type' => 'asset',
                        'sub_type' => 'current_asset',
                        'normal_balance' => 'debit',
                        'level' => 2,
                        'children' => [
                            [
                                'code' => '11100',
                                'name' => 'Cash',
                                'type' => 'asset',
                                'sub_type' => 'current_asset',
                                'normal_balance' => 'debit',
                                'level' => 3,
                                'is_cash_flow' => true,
                            ],
                            [
                                'code' => '11200',
                                'name' => 'Accounts Receivable',
                                'type' => 'asset',
                                'sub_type' => 'current_asset',
                                'normal_balance' => 'debit',
                                'level' => 3,
                            ],
                            [
                                'code' => '11300',
                                'name' => 'Inventory',
                                'type' => 'asset',
                                'sub_type' => 'current_asset',
                                'normal_balance' => 'debit',
                                'level' => 3,
                            ],
                            [
                                'code' => '1999-IBC',
                                'name' => 'Inter-Branch Clearing',
                                'type' => 'asset',
                                'sub_type' => 'current_asset',
                                'normal_balance' => 'debit',
                                'level' => 3,
                                'is_cash_flow' => false,
                            ],
                        ],
                    ],
                    [
                        'code' => '12000',
                        'name' => 'Non-Current Assets',
                        'type' => 'asset',
                        'sub_type' => 'non_current_asset',
                        'normal_balance' => 'debit',
                        'level' => 2,
                        'children' => [
                            [
                                'code' => '12100',
                                'name' => 'Equipment',
                                'type' => 'asset',
                                'sub_type' => 'non_current_asset',
                                'normal_balance' => 'debit',
                                'level' => 3,
                            ],
                            [
                                'code' => '12200',
                                'name' => 'Accumulated Depreciation - Equipment',
                                'type' => 'asset',
                                'sub_type' => 'non_current_asset',
                                'normal_balance' => 'credit',
                                'level' => 3,
                            ],
                        ],
                    ],
                ],
            ],
            // Liabilities
            [
                'code' => '20000',
                'name' => 'Liabilities',
                'type' => 'liability',
                'normal_balance' => 'credit',
                'level' => 1,
                'children' => [
                    [
                        'code' => '21000',
                        'name' => 'Current Liabilities',
                        'type' => 'liability',
                        'sub_type' => 'current_liability',
                        'normal_balance' => 'credit',
                        'level' => 2,
                        'children' => [
                            [
                                'code' => '21100',
                                'name' => 'Accounts Payable',
                                'type' => 'liability',
                                'sub_type' => 'current_liability',
                                'normal_balance' => 'credit',
                                'level' => 3,
                            ],
                            [
                                'code' => '21150',
                                'name' => 'Goods Received Not Invoiced (GR/IR)',
                                'type' => 'liability',
                                'sub_type' => 'current_liability',
                                'normal_balance' => 'credit',
                                'level' => 3,
                            ],
                        ],
                    ],
                ],
            ],
            // Equity
            [
                'code' => '30000',
                'name' => 'Equity',
                'type' => 'equity',
                'normal_balance' => 'credit',
                'level' => 1,
                'children' => [
                    [
                        'code' => '31000',
                        'name' => 'Owner Capital',
                        'type' => 'equity',
                        'normal_balance' => 'credit',
                        'level' => 2,
                    ],
                    [
                        'code' => '32000',
                        'name' => 'Retained Earnings',
                        'type' => 'equity',
                        'normal_balance' => 'credit',
                        'level' => 2,
                    ],
                ],
            ],
            // Revenue
            [
                'code' => '40000',
                'name' => 'Revenue',
                'type' => 'revenue',
                'normal_balance' => 'credit',
                'level' => 1,
                'children' => [
                    [
                        'code' => '41000',
                        'name' => 'Sales Revenue',
                        'type' => 'revenue',
                        'normal_balance' => 'credit',
                        'level' => 2,
                    ],
                    [
                        'code' => '41900',
                        'name' => 'Sales Discount',
                        'type' => 'revenue',
                        'normal_balance' => 'debit',
                        'level' => 2,
                    ],
                ],
            ],
            // Expense
            [
                'code' => '50000',
                'name' => 'Expense',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'level' => 1,
                'children' => [
                    [
                        'code' => '51000',
                        'name' => 'Cost of Goods Sold',
                        'type' => 'expense',
                        'normal_balance' => 'debit',
                        'level' => 2,
                    ],
                    [
                        'code' => '51900',
                        'name' => 'Purchase Discount',
                        'type' => 'expense',
                        'normal_balance' => 'credit',
                        'level' => 2,
                    ],
                    [
                        'code' => '52000',
                        'name' => 'Operating Expense',
                        'type' => 'expense',
                        'normal_balance' => 'debit',
                        'level' => 2,
                    ],
                    [
                        'code' => '53000',
                        'name' => 'Depreciation Expense',
                        'type' => 'expense',
                        'normal_balance' => 'debit',
                        'level' => 3,
                    ],
                ],
            ],
        ];
    }

    private function getAccounts2026(): array
    {
        return [
            // Assets
            [
                'code' => '10000',
                'name' => 'Assets',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'level' => 1,
                'children' => [
                    [
                        'code' => '11000',
                        'name' => 'Current Assets',
                        'type' => 'asset',
                        'sub_type' => 'current_asset',
                        'normal_balance' => 'debit',
                        'level' => 2,
                        'children' => [
                            // CHANGE 1: Split Cash into Cash in Bank and Petty Cash
                            [
                                'code' => '11110',
                                'name' => 'Cash in Banks',
                                'type' => 'asset',
                                'sub_type' => 'current_asset',
                                'normal_balance' => 'debit',
                                'level' => 3,
                                'is_cash_flow' => true,
                            ],
                            [
                                'code' => '11120',
                                'name' => 'Petty Cash',
                                'type' => 'asset',
                                'sub_type' => 'current_asset',
                                'normal_balance' => 'debit',
                                'level' => 3,
                                'is_cash_flow' => true,
                            ],
                            [
                                'code' => '11200',
                                'name' => 'Accounts Receivable',
                                'type' => 'asset',
                                'sub_type' => 'current_asset',
                                'normal_balance' => 'debit',
                                'level' => 3,
                            ],
                            [
                                'code' => '11300',
                                'name' => 'Inventory',
                                'type' => 'asset',
                                'sub_type' => 'current_asset',
                                'normal_balance' => 'debit',
                                'level' => 3,
                            ],
                            [
                                'code' => '1999-IBC',
                                'name' => 'Inter-Branch Clearing',
                                'type' => 'asset',
                                'sub_type' => 'current_asset',
                                'normal_balance' => 'debit',
                                'level' => 3,
                                'is_cash_flow' => false,
                            ],
                        ],
                    ],
                    [
                        'code' => '12000',
                        'name' => 'Non-Current Assets',
                        'type' => 'asset',
                        'sub_type' => 'non_current_asset',
                        'normal_balance' => 'debit',
                        'level' => 2,
                        'children' => [
                            [
                                'code' => '12100',
                                'name' => 'Equipment',
                                'type' => 'asset',
                                'sub_type' => 'non_current_asset',
                                'normal_balance' => 'debit',
                                'level' => 3,
                            ],
                            [
                                'code' => '12200',
                                'name' => 'Accumulated Depreciation - Equipment',
                                'type' => 'asset',
                                'sub_type' => 'non_current_asset',
                                'normal_balance' => 'credit',
                                'level' => 3,
                            ],
                        ],
                    ],
                ],
            ],
            // Liabilities
            [
                'code' => '20000',
                'name' => 'Liabilities',
                'type' => 'liability',
                'normal_balance' => 'credit',
                'level' => 1,
                'children' => [
                    [
                        'code' => '21000',
                        'name' => 'Current Liabilities',
                        'type' => 'liability',
                        'sub_type' => 'current_liability',
                        'normal_balance' => 'credit',
                        'level' => 2,
                        'children' => [
                            [
                                'code' => '21100',
                                'name' => 'Accounts Payable',
                                'type' => 'liability',
                                'sub_type' => 'current_liability',
                                'normal_balance' => 'credit',
                                'level' => 3,
                            ],
                            [
                                'code' => '21150',
                                'name' => 'Goods Received Not Invoiced (GR/IR)',
                                'type' => 'liability',
                                'sub_type' => 'current_liability',
                                'normal_balance' => 'credit',
                                'level' => 3,
                            ],
                        ],
                    ],
                ],
            ],
            // Equity
            [
                'code' => '30000',
                'name' => 'Equity',
                'type' => 'equity',
                'normal_balance' => 'credit',
                'level' => 1,
                'children' => [
                    [
                        'code' => '31000',
                        'name' => 'Owner Capital',
                        'type' => 'equity',
                        'normal_balance' => 'credit',
                        'level' => 2,
                    ],
                    [
                        'code' => '32000',
                        'name' => 'Retained Earnings',
                        'type' => 'equity',
                        'normal_balance' => 'credit',
                        'level' => 2,
                    ],
                ],
            ],
            // Revenue
            [
                'code' => '40000',
                'name' => 'Revenue',
                'type' => 'revenue',
                'normal_balance' => 'credit',
                'level' => 1,
                'children' => [
                    [
                        'code' => '41000',
                        'name' => 'Sales Revenue',
                        'type' => 'revenue',
                        'normal_balance' => 'credit',
                        'level' => 2,
                    ],
                    [
                        'code' => '41900',
                        'name' => 'Sales Discount',
                        'type' => 'revenue',
                        'normal_balance' => 'debit',
                        'level' => 2,
                    ],
                ],
            ],
            // Expense
            [
                'code' => '50000',
                'name' => 'Expense',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'level' => 1,
                'children' => [
                    [
                        'code' => '51000',
                        'name' => 'Cost of Goods Sold',
                        'type' => 'expense',
                        'normal_balance' => 'debit',
                        'level' => 2,
                    ],
                    [
                        'code' => '51900',
                        'name' => 'Purchase Discount',
                        'type' => 'expense',
                        'normal_balance' => 'credit',
                        'level' => 2,
                    ],
                    // CHANGE 2: Renamed Operating Expense to General & Admin Expense
                    [
                        'code' => '52000',
                        'name' => 'General & Admin Expense',
                        'type' => 'expense',
                        'normal_balance' => 'debit',
                        'level' => 2,
                    ],
                    [
                        'code' => '53000',
                        'name' => 'Depreciation Expense',
                        'type' => 'expense',
                        'normal_balance' => 'debit',
                        'level' => 3,
                    ],
                ],
            ],
        ];
    }
}
