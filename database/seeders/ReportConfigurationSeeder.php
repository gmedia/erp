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
            ['assets_header', 'ASET', ReportSection::TYPE_HEADER, null, null, null, null, null],
            ['current_assets', 'Aset Lancar', ReportSection::TYPE_DETAIL, 'asset', 'current_asset', null, null, null],
            ['fixed_assets', 'Aset Tetap', ReportSection::TYPE_DETAIL, 'asset', 'non_current_asset', null, null, null],
            ['other_assets', 'Aset Lainnya', ReportSection::TYPE_DETAIL, 'asset', 'other_asset', null, null, null],
            ['total_assets', 'TOTAL ASET', ReportSection::TYPE_TOTAL, 'asset', null, null, null, null],

            ['liabilities_header', 'KEWAJIBAN', ReportSection::TYPE_HEADER, null, null, null, null, null],
            ['current_liabilities', 'Kewajiban Lancar', ReportSection::TYPE_DETAIL, 'liability', 'current_liability', null, null, null],
            ['long_term_liabilities', 'Kewajiban Jangka Panjang', ReportSection::TYPE_DETAIL, 'liability', 'long_term_liability', null, null, null],
            ['total_liabilities', 'TOTAL KEWAJIBAN', ReportSection::TYPE_TOTAL, 'liability', null, null, null, null],

            ['equity_header', 'EKUITAS', ReportSection::TYPE_HEADER, null, null, null, null, null],
            ['paid_in_capital', 'Paid-in Capital', ReportSection::TYPE_DETAIL, 'equity', null, null, null, ['31000']],
            ['retained_earnings', 'Laba Ditahan', ReportSection::TYPE_DETAIL, 'equity', null, null, null, ['32000']],
            ['current_year_earnings', 'Laba Bersih Tahun Berjalan', ReportSection::TYPE_DETAIL, null, null, null, null, null],
            ['total_equity', 'TOTAL EKUITAS', ReportSection::TYPE_TOTAL, 'equity', null, null, null, null],

            ['total_liab_equity', 'TOTAL KEWAJIBAN + EKUITAS', ReportSection::TYPE_TOTAL, null, null, null, '{total_liabilities} + {total_equity}', null],
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
            ['revenue_header', 'PENDAPATAN', ReportSection::TYPE_HEADER, null, null, null, null, null],
            ['operating_revenue', 'Pendapatan Operasional', ReportSection::TYPE_DETAIL, 'revenue', null, null, null, ['41000', '41900']],
            ['non_operating_revenue', 'Pendapatan Lain-lain', ReportSection::TYPE_DETAIL, 'revenue', null, null, null, ['41800']],
            ['total_revenue', 'TOTAL PENDAPATAN', ReportSection::TYPE_SUBTOTAL, 'revenue', null, null, null, null],

            ['cogs_header', 'HARGA POKOK PENJUALAN', ReportSection::TYPE_HEADER, null, null, null, null, null],
            ['cost_of_goods_sold', 'Harga Pokok Penjualan', ReportSection::TYPE_DETAIL, 'expense', null, null, null, ['51000', '51900']],
            ['gross_profit', 'LABA KOTOR', ReportSection::TYPE_SUBTOTAL, null, null, null, '{total_revenue} - {cost_of_goods_sold}', null],

            ['operating_expense_header', 'BEBAN OPERASIONAL', ReportSection::TYPE_HEADER, null, null, null, null, null],
            ['operating_expense', 'Beban Operasional', ReportSection::TYPE_DETAIL, 'expense', null, null, null, ['52000']],
            ['depreciation_expense', 'Beban Penyusutan', ReportSection::TYPE_DETAIL, 'expense', null, null, null, ['53000']],
            ['operating_income', 'LABA OPERASIONAL', ReportSection::TYPE_SUBTOTAL, null, null, null, '{gross_profit} - {operating_expense} - {depreciation_expense}', null],

            ['non_operating_expense_header', 'BEBAN LAIN-LAIN', ReportSection::TYPE_HEADER, null, null, null, null, null],
            ['non_operating_expense', 'Beban Lain-lain', ReportSection::TYPE_DETAIL, 'expense', null, null, null, ['53100']],
            ['income_before_tax', 'LABA SEBELUM PAJAK', ReportSection::TYPE_SUBTOTAL, null, null, null, '{operating_income} - {non_operating_expense}', null],

            ['tax_expense', 'Beban Pajak', ReportSection::TYPE_DETAIL, null, null, null, null, null],
            ['net_income', 'LABA BERSIH', ReportSection::TYPE_TOTAL, null, null, null, null, null],
        ];

        $this->seedSections($config, $sections);
    }

    private function seedCashFlow(): void
    {
        $config = ReportConfiguration::updateOrCreate(
            ['code' => 'cash_flow'],
            [
                'name' => 'Cash Flow Statement',
                'description' => 'Cash receipts and payments for the period (direct method).',
                'report_type' => ReportConfiguration::TYPE_CASH_FLOW,
                'is_active' => true,
            ]
        );

        $sections = [
            ['operating_header', 'CASH RECEIPTS AND PAYMENTS', ReportSection::TYPE_HEADER, null, null, null, null, null],
            ['cash_inflow', 'Total Cash Inflow', ReportSection::TYPE_SUBTOTAL, null, null, null, null, null],
            ['cash_outflow', 'Total Cash Outflow', ReportSection::TYPE_SUBTOTAL, null, null, null, null, null],
            ['net_change', 'NET INCREASE/DECREASE IN CASH', ReportSection::TYPE_TOTAL, null, null, null, '{cash_inflow} - {cash_outflow}', null],
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

        // Kedua section tanpa filter akun dan tanpa formula mengambil nilai dari
        // total trial balance (debit net) yang dipasok ReportController, dikunci
        // berdasarkan kode section. Tidak ada formula agar tidak tergantung urutan.
        $sections = [
            ['all_accounts', 'Semua Akun', ReportSection::TYPE_DETAIL, null, null, null, null, null],
            ['total_debit_credit', 'TOTAL', ReportSection::TYPE_TOTAL, null, null, null, null, null],
        ];

        $this->seedSections($config, $sections);
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string, 3: ?string, 4: ?string, 5: ?string, 6: ?string, 7: ?array<int, string>}>  $sections
     */
    private function seedSections(ReportConfiguration $config, array $sections): void
    {
        $codes = array_column($sections, 0);

        ReportSection::query()
            ->where('report_configuration_id', $config->id)
            ->whereNotIn('code', $codes)
            ->delete();

        foreach ($sections as $order => [$code, $name, $type, $accountType, $subType, $sign, $formula, $accountCodes]) {
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
                    'account_codes' => $accountCodes,
                    'sign_convention' => $sign ?? ReportSection::SIGN_NORMAL,
                    'formula' => $formula,
                    'is_active' => true,
                ]
            );
        }
    }
}
