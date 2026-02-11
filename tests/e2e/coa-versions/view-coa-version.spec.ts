import { test, expect } from '@playwright/test';
import { createCoaVersion, searchCoaVersion } from '../helpers';

test.describe('COA Version View', () => {
    test('should view coa version details', async ({ page }) => {
        const name = await createCoaVersion(page);
        
        await searchCoaVersion(page, name);

        const row = page.locator('tr', { hasText: name }).first();
        await expect(row).toBeVisible();

        const actionsBtn = row.getByRole('button', { name: /Actions/i });
        await actionsBtn.click();

        const viewItem = page.getByRole('menuitem', { name: /View/i });
        await viewItem.click();

        // Verify modal content
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();
        
        // Wait for title - adjusting based on Fiscal Year experience
        await expect(dialog).toContainText('View COA Version');
        
        // Verify specific details are visible
        await expect(dialog).toContainText(name);
    });
});
