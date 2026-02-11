import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Asset Models - Checkbox', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/asset-models');
    });

    test('should have checkboxes in rows but not in header', async ({ page }) => {
        // Wait for table to load
        await expect(page.locator('table')).toBeVisible();

        // Check header checkbox (should NOT exist based on requirements)
        // verify there is no checkbox in the header row's first cell
        // typically header is thead tr th
        const headerCheckbox = page.locator('thead tr th').first().locator('input[type="checkbox"]');
        await expect(headerCheckbox).not.toBeVisible();

        // Check body row checkboxes (should exist)
        const firstRowCheckbox = page.locator('tbody tr').first().locator('td').first().locator('button[role="checkbox"]');
        await expect(firstRowCheckbox).toBeVisible();
        
        // Select a row
        await firstRowCheckbox.click();
        await expect(firstRowCheckbox).toHaveAttribute('aria-checked', 'true');
    });
});
