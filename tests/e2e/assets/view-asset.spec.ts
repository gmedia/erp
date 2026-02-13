import { test, expect } from '@playwright/test';
import { createAsset } from '../helpers';

test('view asset from actions dropdown', async ({ page }) => {
    const assetCode = await createAsset(page);
    await page.goto('/assets');
    await page.waitForLoadState('networkidle');

    // Find the row
    const row = page.locator('tbody tr').filter({ hasText: assetCode }).first();
    
    // Open Actions dropdown
    const actionsBtn = row.getByRole('button', { name: 'Actions' });
    await actionsBtn.click();
    
    const viewBtn = page.getByRole('menuitem', { name: 'View' });
    await expect(viewBtn).toBeVisible();
    
    const viewItem = page.getByRole('menuitem', { name: /View/i });
    await expect(viewItem).toBeVisible();
    
    // Check if the menuitem itself is the link (asChild) or has a link inside
    const href = await viewItem.getAttribute('href');
    if (href) {
        expect(href).toMatch(/\/assets\/\w+/);
        await viewItem.click({ force: true });
    } else {
        const link = viewItem.locator('a');
        await expect(link).toHaveAttribute('href', /\/assets\/\w+/);
        await link.click({ force: true });
    }
    
    // Verify navigation
    await expect(page).toHaveURL(/\/assets\/\w+/, { timeout: 10000 });
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1')).toContainText(/Asset Details|Test Asset/i);
    await expect(page.locator('body')).toContainText(assetCode);
});
