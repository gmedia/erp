import { test, expect } from '@playwright/test';
import { login } from '../helpers';
import { createAsset, deleteAsset, searchAsset } from '../assets/helpers';

test.describe('Approval History', () => {
  test('displays approval history tab on asset profile', async ({ page }) => {
    const timestamp = Date.now();
    const assetName = `Asset For Approval ${timestamp}`;

    // Create an asset
    await createAsset(page, { name: assetName });

    // Navigate to the asset profile
    await page.goto('/assets');
    await searchAsset(page, assetName);
    
    // Find the asset row and click View
    const row = page.locator('tr', { hasText: assetName }).first();
    await expect(row).toBeVisible();
    
    const actionsCell = row.locator('td').last();
    // Click the actions menu button
    const menuBtn = actionsCell.locator('button[aria-haspopup="menu"], button[role="button"]').first();
    await menuBtn.click();
    
    // Click View
    const viewItem = page.getByRole('menuitem', { name: /View/i });
    await viewItem.click();

    // Verify we are on the profile page
    await expect(page).toHaveURL(/\/assets\/\w+/);

    // Click on Approvals tab
    const approvalsTab = page.getByRole('tab', { name: /Approvals/i });
    await approvalsTab.click();

    // Wait for the API response
    await page.waitForResponse(
        r => r.url().includes('/api/entity-states/asset/') && r.url().includes('/approvals') && r.status() < 400
    ).catch(() => null);

    // Verify the tab panel is visible
    const tabContent = page.getByRole('tabpanel', { name: /Approvals/i });
    await expect(tabContent).toBeVisible();

    // Verify the component renders its content
    // Since we just created it, it might not have any history, so it'll show the empty state or an empty list.
    // Let's verify that there's some text containing "Approval" or something that proves it didn't crash.
    await expect(tabContent.locator('text=Approval').first()).toBeVisible();

    // Cleanup
    await page.goto('/assets');
    await deleteAsset(page, assetName);
  });
});
