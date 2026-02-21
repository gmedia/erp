import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../helpers/auth';

test.describe('Asset Depreciation Runs', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsAdmin(page);
    });

    test('can access index page and open calculate modal', async ({ page }) => {
        await page.goto('/asset-depreciation-runs');
        await expect(page).toHaveTitle(/Asset Depreciation Runs/);
        
        await expect(page.getByRole('heading', { name: 'Depreciation Runs' })).toBeVisible();

        const calculateButton = page.getByRole('button', { name: 'Run Calculation' });
        await expect(calculateButton).toBeVisible();
        await calculateButton.click();

        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();
        await expect(dialog.getByRole('heading', { name: 'Calculate Depreciation' })).toBeVisible();
        
        await dialog.getByRole('button', { name: 'Cancel' }).click();
        await expect(dialog).toBeHidden();
    });
});
