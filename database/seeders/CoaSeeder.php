<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
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
        $this->seedAccountsRecursive($accounts2025, $coaVersion2025->id);


        // ==========================================
        // 2026 Data (Tahun Berjalan - Active)
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

        // 2026 structure with changes:
        // 1. "Cash" (11100) split into "Cash in Bank" (11110) and "Petty Cash" (11120)
        // 2. Renamed "Operating Expense" (52000) to "General & Admin Expense" (52000)
        $accounts2026 = $this->getAccounts2026();
        $this->seedAccountsRecursive($accounts2026, $coaVersion2026->id);
        
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
        $this->createAccountMappings($accountMap2025, $accountMap2026);


        // ==========================================
        // Sample Journal Entries (2026)
        // ==========================================
        $this->seedSampleJournalEntries($fiscalYear2026, $accountMap2026);
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
            if (!empty($children)) {
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
     * Create sample journal entries for demonstration
     */
    private function seedSampleJournalEntries(FiscalYear $fiscalYear, array $accountMap): void
    {
        $user = User::first();

        // Sample Journal 1: Cash Sales
        $journal1 = JournalEntry::create([
            'fiscal_year_id' => $fiscalYear->id,
            'entry_number' => 'JV-2026-00001',
            'entry_date' => '2026-01-15',
            'reference' => 'INV-001',
            'description' => 'Penjualan tunai produk',
            'status' => 'posted',
            'created_by' => $user?->id,
            'posted_by' => $user?->id,
            'posted_at' => now(),
        ]);

        // Debit: Cash in Bank (11110) - Asset bertambah
        JournalEntryLine::create([
            'journal_entry_id' => $journal1->id,
            'account_id' => $accountMap['11110'],
            'debit' => 5000000,
            'credit' => 0,
            'memo' => 'Penerimaan kas dari penjualan',
        ]);

        // Credit: Sales Revenue (41000) - Revenue bertambah
        JournalEntryLine::create([
            'journal_entry_id' => $journal1->id,
            'account_id' => $accountMap['41000'],
            'debit' => 0,
            'credit' => 5000000,
            'memo' => 'Pendapatan penjualan',
        ]);


        // Sample Journal 2: Purchase on Account
        $journal2 = JournalEntry::create([
            'fiscal_year_id' => $fiscalYear->id,
            'entry_number' => 'JV-2026-00002',
            'entry_date' => '2026-01-20',
            'reference' => 'PO-001',
            'description' => 'Pembelian barang dagangan secara kredit',
            'status' => 'posted',
            'created_by' => $user?->id,
            'posted_by' => $user?->id,
            'posted_at' => now(),
        ]);

        // Debit: Inventory (11300) - Asset bertambah
        JournalEntryLine::create([
            'journal_entry_id' => $journal2->id,
            'account_id' => $accountMap['11300'],
            'debit' => 3000000,
            'credit' => 0,
            'memo' => 'Penambahan persediaan barang',
        ]);

        // Credit: Accounts Payable (21100) - Liability bertambah
        JournalEntryLine::create([
            'journal_entry_id' => $journal2->id,
            'account_id' => $accountMap['21100'],
            'debit' => 0,
            'credit' => 3000000,
            'memo' => 'Utang dagang kepada supplier',
        ]);


        // Sample Journal 3: Pay Operating Expense (Draft)
        $journal3 = JournalEntry::create([
            'fiscal_year_id' => $fiscalYear->id,
            'entry_number' => 'JV-2026-00003',
            'entry_date' => '2026-01-25',
            'reference' => 'EXP-001',
            'description' => 'Pembayaran biaya listrik dan telepon',
            'status' => 'draft',
            'created_by' => $user?->id,
        ]);

        // Debit: General & Admin Expense (52000) - Expense bertambah
        JournalEntryLine::create([
            'journal_entry_id' => $journal3->id,
            'account_id' => $accountMap['52000'],
            'debit' => 500000,
            'credit' => 0,
            'memo' => 'Biaya utilitas bulan Januari',
        ]);

        // Credit: Petty Cash (11120) - Asset berkurang
        JournalEntryLine::create([
            'journal_entry_id' => $journal3->id,
            'account_id' => $accountMap['11120'],
            'debit' => 0,
            'credit' => 500000,
            'memo' => 'Pengeluaran dari kas kecil',
        ]);
    }

    private function getAccounts2025(): array
    {
        return [
            // Assets
            [
                'code' => '10000', 'name' => 'Assets', 'type' => 'asset', 'normal_balance' => 'debit', 'level' => 1,
                'children' => [
                    ['code' => '11000', 'name' => 'Current Assets', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit', 'level' => 2, 'children' => [
                        ['code' => '11100', 'name' => 'Cash', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit', 'level' => 3, 'is_cash_flow' => true],
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
                    ['code' => '32000', 'name' => 'Retained Earnings', 'type' => 'equity', 'normal_balance' => 'credit', 'level' => 2],
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

    private function getAccounts2026(): array
    {
        return [
            // Assets
            [
                'code' => '10000', 'name' => 'Assets', 'type' => 'asset', 'normal_balance' => 'debit', 'level' => 1,
                'children' => [
                    ['code' => '11000', 'name' => 'Current Assets', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit', 'level' => 2, 'children' => [
                        // CHANGE 1: Split Cash into Cash in Bank and Petty Cash
                        ['code' => '11110', 'name' => 'Cash in Banks', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit', 'level' => 3, 'is_cash_flow' => true],
                        ['code' => '11120', 'name' => 'Petty Cash', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit', 'level' => 3, 'is_cash_flow' => true],
                        
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
                    ['code' => '32000', 'name' => 'Retained Earnings', 'type' => 'equity', 'normal_balance' => 'credit', 'level' => 2],
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
                    // CHANGE 2: Renamed Operating Expense to General & Admin Expense
                    ['code' => '52000', 'name' => 'General & Admin Expense', 'type' => 'expense', 'normal_balance' => 'debit', 'level' => 2], 
                ]
            ],
        ];
    }
}
