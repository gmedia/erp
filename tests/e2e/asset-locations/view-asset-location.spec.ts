import { test, expect } from '@playwright/test';
import { createAssetLocation, login, searchAssetLocation } from '../helpers';

test('view asset location details', async ({ page }) => {
  const name = await createAssetLocation(page);

  await login(page);
  await page.goto('/asset-locations');

  await searchAssetLocation(page, name);

  const row = page.locator('tr').filter({ hasText: name }).first();
  await expect(row).toBeVisible();

  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await actionsBtn.click();

  const viewItem = page.getByRole('menuitem', { name: /View/i });
  await viewItem.click();

  // Verify we are on the view page or a dialog/drawer opened
  await page.waitForTimeout(1000); // Wait for animation
  
  // Check for the title
  await expect(page.getByText(/Asset Location Details|Location Details/i)).toBeVisible();
  
  // Check for the name in the details
  // Use a more specific locator if the name is in a description list or similar
  const detailValue = page.locator('div, dl, td, span').filter({ hasText: name }).first();
  await expect(detailValue).toBeVisible();
});
