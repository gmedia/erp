import { test, expect } from '@playwright/test';
import { createFiscalYear, searchFiscalYear } from '../helpers';

test.describe('Fiscal Year View', () => {
    test('should view fiscal year details', async ({ page }) => {
        // Create a fiscal year to view
        const name = await createFiscalYear(page);

        // Search for it to isolate
        await searchFiscalYear(page, name);

        // Click view action on the row
        const row = page.locator('tr', { hasText: name }).first();
        await expect(row).toBeVisible();

        const actionsBtn = row.getByRole('button', { name: /Actions/i });
        await actionsBtn.click();

        const viewItem = page.getByRole('menuitem', { name: /View/i });
        await viewItem.click();

        // Verify modal content
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();
        await expect(dialog).toContainText('View Fiscal Year');
        
        // Verify specific details are visible
        await expect(dialog).toContainText(name);
        // Add checks for status, dates if needed, but name verification is usually sufficient for "View" test
    });
});
