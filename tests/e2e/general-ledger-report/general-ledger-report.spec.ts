import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('General Ledger Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can navigate to report page and see filters', async ({ page }) => {
        await page.goto('/reports/general-ledger');
        await page.waitForURL('**/reports/general-ledger', { timeout: 15000 });

        const filterButton = page.getByRole('button', { name: /filter/i });
        await expect(filterButton).toBeVisible({ timeout: 15000 });
        await filterButton.click();

        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();

        const accountTrigger = filtersDialog.locator('button[role="combobox"]').first();
        await expect(accountTrigger).toBeVisible();

        const applyBtn = filtersDialog.getByRole('button', { name: /Apply Filters/i });
        await expect(applyBtn).toBeVisible();
    });
});
