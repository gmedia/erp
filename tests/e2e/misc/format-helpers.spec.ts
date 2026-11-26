import { expect, test } from '@playwright/test';
import { SUPPORTED_LOCALES } from '../../../resources/js/lib/locale-storage';

type Formatters = typeof import('../../../resources/js/utils/formatters');
type NumberFormat = typeof import('../../../resources/js/utils/number-format');
type DateFormat = typeof import('../../../resources/js/utils/date-format');

async function loadModules() {
    const formatters: Formatters = await import(
        '../../../resources/js/utils/formatters'
    );
    const numberFormat: NumberFormat = await import(
        '../../../resources/js/utils/number-format'
    );
    const dateFormat: DateFormat = await import(
        '../../../resources/js/utils/date-format'
    );

    return { formatters, numberFormat, dateFormat };
}

function applyCompanySettings(
    numberFormat: NumberFormat,
    dateFormat: DateFormat,
    overrides: Partial<{
        currency: string;
        number_format_decimal: string;
        number_format_thousand: string;
        number_format_hide_decimal: boolean;
        date_format: string;
    }> = {},
) {
    numberFormat.setRegionalNumberFormatSettings({
        currency: 'IDR',
        number_format_decimal: ',',
        number_format_thousand: '.',
        number_format_hide_decimal: false,
        ...overrides,
    });
    dateFormat.setRegionalDateFormatSettings({
        date_format: overrides.date_format ?? 'd/m/Y',
    });
}

function setUiLocale(locale: string) {
    globalThis.localStorage?.setItem('app_locale', locale);
}

test.describe('format helpers - pengaturan regional perusahaan', () => {
    test.beforeEach(() => {
        const host = globalThis as Record<string, unknown>;
        delete host.__APP_REGIONAL_NUMBER_FORMAT_SETTINGS__;
        delete host.__APP_REGIONAL_DATE_FORMAT_SETTINGS__;
    });

    test('formatNumber memakai pemisah dari Setting, bukan locale UI', async () => {
        const { formatters, numberFormat, dateFormat } = await loadModules();
        applyCompanySettings(numberFormat, dateFormat);

        setUiLocale('en');
        expect(formatters.formatNumber(1234567.5)).toBe('1.234.567,5');

        applyCompanySettings(numberFormat, dateFormat, {
            number_format_decimal: '.',
            number_format_thousand: ',',
        });
        setUiLocale('id');
        expect(formatters.formatNumber(1234567.5)).toBe('1,234,567.5');
    });

    test('formatNumber menghormati number_format_hide_decimal', async () => {
        const { formatters, numberFormat, dateFormat } = await loadModules();
        applyCompanySettings(numberFormat, dateFormat, {
            number_format_hide_decimal: true,
        });

        expect(formatters.formatNumber(1234.56)).toBe('1.235');
    });

    test('formatCurrency memakai simbol dari Setting currency', async () => {
        const { formatters, numberFormat, dateFormat } = await loadModules();
        applyCompanySettings(numberFormat, dateFormat, { currency: 'USD' });

        const result = formatters.formatCurrency(1500);
        expect(result).toContain('1.500');
        expect(result).toMatch(/US\$|\$/);
    });

    test('formatCurrency tanpa Setting currency menampilkan angka tanpa simbol (tanpa fallback IDR)', async () => {
        const { formatters, numberFormat, dateFormat } = await loadModules();
        applyCompanySettings(numberFormat, dateFormat, { currency: '' });

        const result = formatters.formatCurrency(1500);
        expect(result).toBe('1.500');
        expect(result).not.toContain('Rp');
        expect(result).not.toContain('IDR');
    });

    test('formatCurrency memakai nilai mata uang eksplisit bila diberikan', async () => {
        const { formatters, numberFormat, dateFormat } = await loadModules();
        applyCompanySettings(numberFormat, dateFormat, { currency: '' });

        expect(formatters.formatCurrency(1500, { currency: 'USD' })).toMatch(
            /US\$|\$/,
        );
    });

    test('nilai kosong atau tidak valid menjadi "-"', async () => {
        const { formatters, numberFormat, dateFormat } = await loadModules();
        applyCompanySettings(numberFormat, dateFormat);

        expect(formatters.formatNumber(null)).toBe('-');
        expect(formatters.formatNumber('')).toBe('-');
        expect(formatters.formatCurrency(undefined)).toBe('-');
        expect(formatters.formatCurrency('abc')).toBe('-');
        expect(formatters.formatDate(null)).toBe('-');
        expect(formatters.formatDate('bukan-tanggal')).toBe('-');
    });

    test('formatDate memakai pola date_format dari Setting', async () => {
        const { formatters, numberFormat, dateFormat } = await loadModules();
        applyCompanySettings(numberFormat, dateFormat, { date_format: 'd/m/Y' });
        setUiLocale('en');

        const date = new Date(2026, 10, 26);
        expect(formatters.formatDate(date)).toBe('26/11/2026');

        applyCompanySettings(numberFormat, dateFormat, { date_format: 'Y-m-d' });
        expect(formatters.formatDate(date)).toBe('2026-11-26');
    });

    test('formatDate: nama bulan mengikuti locale UI, pola tetap dari Setting', async () => {
        const { formatters, numberFormat, dateFormat } = await loadModules();
        applyCompanySettings(numberFormat, dateFormat, { date_format: 'd M Y' });
        const date = new Date(2026, 10, 26);

        setUiLocale('en');
        expect(formatters.formatDate(date)).toBe('26 Nov 2026');

        setUiLocale('id');
        expect(formatters.formatDate(date)).toBe('26 Nov 2026');
        expect(formatters.formatMonth(date)).toBe('Nov 2026');
    });

    test('formatMonth menampilkan bulan-tahun dan "-" untuk nilai tidak valid', async () => {
        const { formatters, numberFormat, dateFormat } = await loadModules();
        applyCompanySettings(numberFormat, dateFormat);
        setUiLocale('en');

        expect(formatters.formatMonth(new Date(2026, 0, 15))).toBe('Jan 2026');
        expect(formatters.formatMonth(null)).toBe('-');
        expect(formatters.formatMonth('bukan-tanggal')).toBe('-');
    });

    test('formatRupiah tetap ada sebagai alias deprecated dan mengikuti Setting', async () => {
        const { formatters, numberFormat, dateFormat } = await loadModules();
        applyCompanySettings(numberFormat, dateFormat, { currency: '' });

        expect(formatters.formatRupiah(2000)).toBe('2.000');
    });

    test('locale UI yang didukung tercantum (sanity)', () => {
        expect([...SUPPORTED_LOCALES]).toEqual(['en', 'id']);
    });
});
