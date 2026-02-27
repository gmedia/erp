import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Asset Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate and login
    await login(page);
  });

  test('can navigate to asset dashboard from menu and view cards', async ({ page }) => {
    // 1. Open Asset menu and click Asset Dashboard
    await page.getByRole('button', { name: 'Asset', exact: true }).first().click();
    await page.getByRole('link', { name: 'Asset Dashboard' }).click();

    // 2. Wait for navigation
    await expect(page).toHaveURL(/.*\/asset-dashboard/);
    await expect(page.getByRole('heading', { name: 'Asset Management Overview' })).toBeVisible();

    // 3. Verify cards exist and load data (or show 0/Rp0 if empty database)
    const cards = [
      { label: 'Total Assets' },
      { label: 'Purchase Cost' },
      { label: 'Book Value' },
      { label: 'Accum. Depreciation' },
    ];

    for (const card of cards) {
      const container = page
        .locator('.border-l-4') // Our summary cards use border-l-4
        .filter({ hasText: card.label })
        .first();

      await expect(container).toBeVisible({ timeout: 10000 });
      
      // Value should be visible (a number or currency string)
      const value = container.locator('.text-3xl.font-bold').first();
      await expect(value).toBeVisible();
      await expect(value).toHaveText(/[\d.,Rp]+|0/);
    }
    
    // 4. Verify charts and tables sections exist
    await expect(page.getByText('Asset Status Distribution')).toBeVisible();
    await expect(page.getByText('Top Asset Categories')).toBeVisible();
    await expect(page.getByText('Condition Overview')).toBeVisible();
    await expect(page.getByText('Upcoming Maintenance')).toBeVisible();
    await expect(page.getByText('Expiring Warranties')).toBeVisible();
  });
});
