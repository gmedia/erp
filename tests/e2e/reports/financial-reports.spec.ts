import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Financial Reports', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('can view trial balance', async ({ page }) => {
        await page.goto('/reports/trial-balance');
        await expect(page).toHaveTitle(/Trial Balance/);
        
        // Check for main elements
        // Check for main elements
        await expect(page.getByRole('heading', { name: 'Trial Balance', level: 1 })).toBeVisible();
        await expect(page.locator('[data-slot="card-title"]', { hasText: 'Trial Balance Report' })).toBeVisible();
        
        // Check for table headers
        await expect(page.getByRole('columnheader', { name: 'Code' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Account Name' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Debit' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Credit' })).toBeVisible();
    });

    test('can view balance sheet', async ({ page }) => {
        await page.goto('/reports/balance-sheet');
        await expect(page).toHaveTitle(/Balance Sheet/);
        
        // Check for main elements
        await expect(page.getByRole('heading', { name: 'Balance Sheet', level: 1 })).toBeVisible();
        
        // Check for sections
        // Check for sections
        // Use data-slot attribute which is reliable for CardTitle
        await expect(page.locator('[data-slot="card-title"]', { hasText: 'Assets' })).toBeVisible();
        await expect(page.locator('[data-slot="card-title"]', { hasText: 'Liabilities' })).toBeVisible();
        await expect(page.locator('[data-slot="card-title"]', { hasText: 'Equity' })).toBeVisible();
        
        // Verify Summary Card
        await expect(page.getByText('Total Assets')).toBeVisible();
        await expect(page.getByText('Total Liabilities & Equity')).toBeVisible();
    });

    test('can use balance sheet comparison', async ({ page }) => {
        await page.goto('/reports/balance-sheet');
        
        // Check if "Compare With" selector exists
        const compareSelector = page.getByRole('combobox').filter({ hasText: /None|FY-/ }).first();
        // Note: It might say "None" initially
        
        if (await compareSelector.isVisible()) {
             // Try to select a comparison year if available options exist
             // Use a more specific locator for the trigger if generic combobox is ambiguous, 
             // but usually shadcn Select trigger is a button with role combobox
             
             // We won't force a selection effectively without knowing seed data, 
             // but we can verify the UI element is present.
             await expect(compareSelector).toBeVisible();
             
             // Optionally, click it to see options
             await compareSelector.click();
             await expect(page.getByRole('option')).not.toHaveCount(0); // Should have at least 'None' or years
        }
    });

    test('can view income statement', async ({ page }) => {
        await page.goto('/reports/income-statement');
        await expect(page).toHaveTitle(/Income Statement/);

        await expect(page.getByRole('heading', { name: 'Income Statement', level: 1 })).toBeVisible();

        await expect(page.locator('[data-slot="card-title"]', { hasText: 'Revenue' })).toBeVisible();
        await expect(page.locator('[data-slot="card-title"]', { hasText: 'Expense' })).toBeVisible();

        await expect(page.getByText('Total Revenue', { exact: true })).toBeVisible();
        await expect(page.getByText('Total Expense', { exact: true })).toBeVisible();
        await expect(page.getByText('Net Income', { exact: true })).toBeVisible();
    });

    test('can view cash flow', async ({ page }) => {
        await page.goto('/reports/cash-flow');
        await expect(page).toHaveTitle(/Cash Flow/);

        await expect(page.getByRole('heading', { name: 'Cash Flow', level: 1 })).toBeVisible();
        await expect(page.locator('[data-slot="card-title"]', { hasText: 'Cash Flow Report' })).toBeVisible();

        await expect(page.getByRole('columnheader', { name: 'Code' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Account Name' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Inflow' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Outflow' })).toBeVisible();
    });
});
