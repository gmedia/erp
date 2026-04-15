import { test, expect, type Page, type Response } from '@playwright/test';
import { login } from '../helpers';

async function openFinancialReport(
    page: Page,
    path: string,
    endpoint: string,
    title: RegExp,
) {
    await Promise.all([
        page.waitForResponse(
            (response: Response) =>
                response.url().includes(`/api/reports/${endpoint}`) &&
                response.status() < 400,
            { timeout: 30000 },
        ),
        page.goto(path),
    ]);

    await expect(page).toHaveTitle(title);
}

async function waitForBalanceSheetReady(page: Page) {
    await expect(
        page.getByRole('heading', { name: 'Balance Sheet', level: 1 }),
    ).toBeVisible({ timeout: 30000 });
    await expect(page.getByText('Total Assets')).toBeVisible({ timeout: 30000 });
    await expect(
        page.getByText('Total Liabilities & Equity'),
    ).toBeVisible({ timeout: 30000 });
}

test.describe('Financial Reports', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('can view trial balance', async ({ page }) => {
        await openFinancialReport(
            page,
            '/reports/trial-balance',
            'trial-balance',
            /Trial Balance/,
        );

        await expect(
            page.getByRole('heading', { name: 'Trial Balance', level: 1 }),
        ).toBeVisible();
        await expect(page.locator('[data-slot="card-title"]', { hasText: 'Trial Balance Report' })).toBeVisible();

        await expect(page.getByRole('columnheader', { name: 'Code' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Account Name' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Debit' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Credit' })).toBeVisible();
    });

    test('can view balance sheet', async ({ page }) => {
        await openFinancialReport(
            page,
            '/reports/balance-sheet',
            'balance-sheet',
            /Balance Sheet/,
        );

        await waitForBalanceSheetReady(page);
        await expect(
            page.locator('[data-slot="card-title"]', { hasText: 'Assets' }),
        ).toBeVisible({ timeout: 30000 });
    });

    test('can use balance sheet comparison', async ({ page }) => {
        await openFinancialReport(
            page,
            '/reports/balance-sheet',
            'balance-sheet',
            /Balance Sheet/,
        );

        await waitForBalanceSheetReady(page);

        const compareSelector = page.getByRole('combobox').last();
        await expect(compareSelector).toBeVisible({ timeout: 30000 });
        await compareSelector.click();

        await expect(
            page.getByRole('option').filter({ hasText: /None|FY-/ }).first(),
        ).toBeVisible({ timeout: 15000 });
    });

    test('can view income statement', async ({ page }) => {
        await openFinancialReport(
            page,
            '/reports/income-statement',
            'income-statement',
            /Income Statement/,
        );

        await expect(page.getByRole('heading', { name: 'Income Statement', level: 1 })).toBeVisible();

        await expect(page.locator('[data-slot="card-title"]', { hasText: 'Revenue' })).toBeVisible();
        await expect(page.locator('[data-slot="card-title"]', { hasText: 'Expense' })).toBeVisible();

        await expect(page.getByText('Total Revenue', { exact: true })).toBeVisible();
        await expect(page.getByText('Total Expense', { exact: true })).toBeVisible();
        await expect(page.getByText('Net Income', { exact: true })).toBeVisible();
    });

    test('can view cash flow', async ({ page }) => {
        await openFinancialReport(
            page,
            '/reports/cash-flow',
            'cash-flow',
            /Cash Flow/,
        );

        await expect(page.getByRole('heading', { name: 'Cash Flow', level: 1 })).toBeVisible();
        await expect(page.locator('[data-slot="card-title"]', { hasText: 'Cash Flow Report' })).toBeVisible();

        await expect(page.getByRole('columnheader', { name: 'Code' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Account Name' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Inflow' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Outflow' })).toBeVisible();
    });

    test('can view comparative report', async ({ page }) => {
        await openFinancialReport(
            page,
            '/reports/comparative',
            'comparative',
            /Comparative Report/,
        );

        await expect(page.getByRole('heading', { name: 'Comparative Report', level: 1 })).toBeVisible();

        await expect(page.locator('[data-slot="card-title"]', { hasText: 'Assets' })).toBeVisible();
        await expect(page.locator('[data-slot="card-title"]', { hasText: 'Liabilities' })).toBeVisible();
        await expect(page.locator('[data-slot="card-title"]', { hasText: 'Equity' })).toBeVisible();
        await expect(page.locator('[data-slot="card-title"]', { hasText: 'Revenue' })).toBeVisible();
        await expect(page.locator('[data-slot="card-title"]', { hasText: 'Expense' })).toBeVisible();
    });
});
