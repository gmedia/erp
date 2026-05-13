<?php

namespace Database\Seeders;

use App\Models\ReportConfiguration;
use App\Models\ReportSection;
use Illuminate\Database\Seeder;

class ReportConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBalanceSheet();
        $this->seedIncomeStatement();
        $this->seedCashFlow();
        $this->seedTrialBalance();
    }

    private function seedBalanceSheet(): void
    {
        $config = ReportConfiguration::updateOrCreate(
            ['code' => 'balance_sheet'],
            [
                'name' => 'Balance Sheet',
                'description' => 'Posisi keuangan perusahaan pada titik waktu tertentu (Aset = Liabilitas + Ekuitas).',
                'report_type' => ReportConfiguration::TYPE_BALANCE_SHEET,
                'is_active' => true,
            ]
        );

        $sections = [
            ['assets_header', 'ASET', ReportSection::TYPE_HEADER, null, null, null, null],
            ['current_assets', 'Aset Lancar', ReportSection::TYPE_DETAIL, 'asset', 'current_asset', null, null],
            ['fixed_assets', 'Aset Tetap', ReportSection::TYPE_DETAIL, 'asset', 'fixed_asset', null, null],
            ['other_assets', 'Aset Lainnya', ReportSection::TYPE_DETAIL, 'asset', 'other_asset', null, null],
            ['total_assets', 'TOTAL ASET', ReportSection::TYPE_TOTAL, 'asset', null, null, null],

            ['liabilities_header', 'KEWAJIBAN', ReportSection::TYPE_HEADER, null, null, null, null],
            ['current_liabilities', 'Kewajiban Lancar', ReportSection::TYPE_DETAIL, 'liability', 'current_liability', null, null],
            ['long_term_liabilities', 'Kewajiban Jangka Panjang', ReportSection::TYPE_DETAIL, 'liability', 'long_term_liability', null, null],
            ['total_liabilities', 'TOTAL KEWAJIBAN', ReportSection::TYPE_TOTAL, 'liability', null, null, null],

            ['equity_header', 'EKUITAS', ReportSection::TYPE_HEADER, null, null, null, null],
            ['paid_in_capital', 'Modal Disetor', ReportSection::TYPE_DETAIL, 'equity', 'paid_in_capital', null, null],
            ['retained_earnings', 'Laba Ditahan', ReportSection::TYPE_DETAIL, 'equity', 'retained_earnings', null, null],
            ['current_year_earnings', 'Laba Bersih Tahun Berjalan', ReportSection::TYPE_DETAIL, null, null, null, '{revenue} - {expense}'],
            ['total_equity', 'TOTAL EKUITAS', ReportSection::TYPE_TOTAL, 'equity', null, null, null],

            ['total_liab_equity', 'TOTAL KEWAJIBAN + EKUITAS', ReportSection::TYPE_TOTAL, null, null, null, '{total_liabilities} + {total_equity}'],
        ];

        $this->seedSections($config, $sections);
    }

    private function seedIncomeStatement(): void
    {
        $config = ReportConfiguration::updateOrCreate(
            ['code' => 'income_statement'],
            [
                'name' => 'Income Statement',
                'description' => 'Kinerja keuangan selama periode tertentu (Pendapatan - Beban = Laba/Rugi).',
                'report_type' => ReportConfiguration::TYPE_INCOME_STATEMENT,
                'is_active' => true,
            ]
        );

        $sections = [
            ['revenue_header', 'PENDAPATAN', ReportSection::TYPE_HEADER, null, null, null, null],
            ['operating_revenue', 'Pendapatan Operasional', ReportSection::TYPE_DETAIL, 'revenue', 'operating_revenue', null, null],
            ['non_operating_revenue', 'Pendapatan Lain-lain', ReportSection::TYPE_DETAIL, 'revenue', 'non_operating_revenue', null, null],
            ['total_revenue', 'TOTAL PENDAPATAN', ReportSection::TYPE_SUBTOTAL, 'revenue', null, null, null],

            ['cogs_header', 'HARGA POKOK PENJUALAN', ReportSection::TYPE_HEADER, null, null, null, null],
            ['cost_of_goods_sold', 'Harga Pokok Penjualan', ReportSection::TYPE_DETAIL, 'expense', 'cost_of_goods_sold', null, null],
            ['gross_profit', 'LABA KOTOR', ReportSection::TYPE_SUBTOTAL, null, null, null, '{total_revenue} - {cost_of_goods_sold}'],

            ['operating_expense_header', 'BEBAN OPERASIONAL', ReportSection::TYPE_HEADER, null, null, null, null],
            ['operating_expense', 'Beban Operasional', ReportSection::TYPE_DETAIL, 'expense', 'operating_expense', null, null],
            ['depreciation_expense', 'Beban Penyusutan', ReportSection::TYPE_DETAIL, 'expense', 'depreciation_expense', null, null],
            ['operating_income', 'LABA OPERASIONAL', ReportSection::TYPE_SUBTOTAL, null, null, null, '{gross_profit} - {operating_expense} - {depreciation_expense}'],

            ['non_operating_expense_header', 'BEBAN LAIN-LAIN', ReportSection::TYPE_HEADER, null, null, null, null],
            ['non_operating_expense', 'Beban Lain-lain', ReportSection::TYPE_DETAIL, 'expense', 'non_operating_expense', null, null],
            ['income_before_tax', 'LABA SEBELUM PAJAK', ReportSection::TYPE_SUBTOTAL, null, null, null, '{operating_income} - {non_operating_expense}'],

            ['tax_expense', 'Beban Pajak', ReportSection::TYPE_DETAIL, 'expense', 'tax_expense', null, null],
            ['net_income', 'LABA BERSIH', ReportSection::TYPE_TOTAL, null, null, null, '{income_before_tax} - {tax_expense}'],
        ];

        $this->seedSections($config, $sections);
    }

    private function seedCashFlow(): void
    {
        $config = ReportConfiguration::updateOrCreate(
            ['code' => 'cash_flow'],
            [
                'name' => 'Cash Flow Statement',
                'description' => 'Pergerakan kas masuk dan keluar (metode tidak langsung) selama periode tertentu.',
                'report_type' => ReportConfiguration::TYPE_CASH_FLOW,
                'is_active' => true,
            ]
        );

        $sections = [
            ['operating_header', 'ARUS KAS DARI AKTIVITAS OPERASIONAL', ReportSection::TYPE_HEADER, null, null, null, null],
            ['net_income_source', 'Laba Bersih', ReportSection::TYPE_DETAIL, null, null, null, '{net_income}'],
            ['depreciation_addback', 'Penyusutan', ReportSection::TYPE_DETAIL, 'expense', 'depreciation_expense', ReportSection::SIGN_REVERSED, null],
            ['working_capital_changes', 'Perubahan Modal Kerja', ReportSection::TYPE_DETAIL, null, null, null, null],
            ['operating_cash_flow', 'Arus Kas Bersih dari Operasional', ReportSection::TYPE_SUBTOTAL, null, null, null, '{net_income_source} + {depreciation_addback} + {working_capital_changes}'],

            ['investing_header', 'ARUS KAS DARI AKTIVITAS INVESTASI', ReportSection::TYPE_HEADER, null, null, null, null],
            ['fixed_asset_activity', 'Aktivitas Aset Tetap', ReportSection::TYPE_DETAIL, 'asset', 'fixed_asset', ReportSection::SIGN_REVERSED, null],
            ['investing_cash_flow', 'Arus Kas Bersih dari Investasi', ReportSection::TYPE_SUBTOTAL, null, null, null, '{fixed_asset_activity}'],

            ['financing_header', 'ARUS KAS DARI AKTIVITAS PENDANAAN', ReportSection::TYPE_HEADER, null, null, null, null],
            ['long_term_debt_activity', 'Pinjaman Jangka Panjang', ReportSection::TYPE_DETAIL, 'liability', 'long_term_liability', null, null],
            ['equity_activity', 'Modal Disetor', ReportSection::TYPE_DETAIL, 'equity', 'paid_in_capital', null, null],
            ['financing_cash_flow', 'Arus Kas Bersih dari Pendanaan', ReportSection::TYPE_SUBTOTAL, null, null, null, '{long_term_debt_activity} + {equity_activity}'],

            ['net_change', 'KENAIKAN/PENURUNAN KAS BERSIH', ReportSection::TYPE_TOTAL, null, null, null, '{operating_cash_flow} + {investing_cash_flow} + {financing_cash_flow}'],
        ];

        $this->seedSections($config, $sections);
    }

    private function seedTrialBalance(): void
    {
        $config = ReportConfiguration::updateOrCreate(
            ['code' => 'trial_balance'],
            [
                'name' => 'Trial Balance',
                'description' => 'Saldo semua akun untuk memastikan total debit = total kredit.',
                'report_type' => ReportConfiguration::TYPE_TRIAL_BALANCE,
                'is_active' => true,
            ]
        );

        $sections = [
            ['all_accounts', 'Semua Akun', ReportSection::TYPE_DETAIL, null, null, null, null],
            ['total_debit_credit', 'TOTAL', ReportSection::TYPE_TOTAL, null, null, null, null],
        ];

        $this->seedSections($config, $sections);
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string, 3: ?string, 4: ?string, 5: ?string, 6: ?string}>  $sections
     */
    private function seedSections(ReportConfiguration $config, array $sections): void
    {
        foreach ($sections as $order => [$code, $name, $type, $accountType, $subType, $sign, $formula]) {
            ReportSection::updateOrCreate(
                [
                    'report_configuration_id' => $config->id,
                    'code' => $code,
                ],
                [
                    'name' => $name,
                    'sort_order' => ($order + 1) * 10,
                    'section_type' => $type,
                    'account_type_filter' => $accountType,
                    'account_sub_type_filter' => $subType,
                    'sign_convention' => $sign ?? ReportSection::SIGN_NORMAL,
                    'formula' => $formula,
                    'is_active' => true,
                ]
            );
        }
    }
}
