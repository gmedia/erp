import { Page, expect } from '@playwright/test';

/**
 * Shared helpers for cross-module business-flow E2E (issue #355).
 *
 * Rules (see issue #355):
 * - Main actions go through the UI; the API is used only for prerequisites and
 *   for reading numbers.
 * - Amounts are asserted as before/after deltas, never as absolute seed values.
 */

export interface ReportTotals {
    assets: number;
    liabilities: number;
    equity: number;
    revenue: number;
    expense: number;
    netIncome: number;
    trialDebit: number;
    trialCredit: number;
}

export async function getApiToken(page: Page): Promise<string> {
    const token = await page.evaluate(() => localStorage.getItem('api_token'));
    expect(token, 'api_token must exist after login').toBeTruthy();

    return token as string;
}

export async function apiGet<T = unknown>(
    page: Page,
    path: string,
): Promise<T> {
    const token = await getApiToken(page);
    const response = await page.request.get(path, {
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
        },
    });
    expect(response.status(), `GET ${path}`).toBeLessThan(400);

    return (await response.json()) as T;
}

export function toNumber(value: unknown): number {
    const parsed = typeof value === 'number' ? value : Number(value ?? 0);

    return Number.isFinite(parsed) ? parsed : 0;
}

export async function getFiscalYearId(page: Page): Promise<number> {
    const body = await apiGet<{ data?: Array<{ id: number; status: string }> }>(
        page,
        '/api/fiscal-years?per_page=50',
    );
    const rows = body.data ?? [];
    const open = rows.find((row) => row.status === 'open') ?? rows[0];
    if (!open) {
        throw new Error('at least one fiscal year must exist');
    }

    return Number(open.id);
}

/**
 * Read the headline numbers from the three financial reports.
 * Balance sheet and income statement use `report.totals`; the trial balance
 * returns account rows, so its totals are summed from `report`.
 */
export async function readReportTotals(
    page: Page,
    fiscalYearId: number,
): Promise<ReportTotals> {
    const [tb, bs, is] = await Promise.all([
        apiGet<{ report: Array<{ debit: number; credit: number }> }>(
            page,
            `/api/reports/trial-balance?fiscal_year_id=${fiscalYearId}`,
        ),
        apiGet<{ report: { totals: Record<string, unknown> } }>(
            page,
            `/api/reports/balance-sheet?fiscal_year_id=${fiscalYearId}`,
        ),
        apiGet<{ report: { totals: Record<string, unknown> } }>(
            page,
            `/api/reports/income-statement?fiscal_year_id=${fiscalYearId}`,
        ),
    ]);

    const rows = Array.isArray(tb.report) ? tb.report : [];
    const bsTotals = bs.report?.totals ?? {};
    const isTotals = is.report?.totals ?? {};

    return {
        assets: toNumber(bsTotals.assets),
        liabilities: toNumber(bsTotals.liabilities),
        equity: toNumber(bsTotals.equity),
        revenue: toNumber(isTotals.revenue),
        expense: toNumber(isTotals.expense),
        netIncome: toNumber(isTotals.net_income),
        trialDebit: rows.reduce((sum, row) => sum + toNumber(row.debit), 0),
        trialCredit: rows.reduce((sum, row) => sum + toNumber(row.credit), 0),
    };
}

export function formatIdr(amount: number): string {
    return new Intl.NumberFormat('id-ID').format(amount);
}
