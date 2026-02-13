import { test, expect } from '@playwright/test';
import { login, createAsset } from '../helpers';

test('delete existing asset end-to-end', async ({ page }) => {
  // Create a fresh asset to delete
  const freshAssetCode = await createAsset(page);

  await page.goto('/assets');

  // Wait for the table to load
  await page.waitForSelector('table');

  // Search for the fresh asset
  let searchInput = page.getByPlaceholder(/Search assets.../i);
  await searchInput.clear();
  await searchInput.fill(freshAssetCode);
  await Promise.all([
    page.waitForResponse(response => 
      response.url().includes('/api/assets') && 
      response.url().includes(`search=${freshAssetCode}`) &&
      response.status() === 200
    ),
    searchInput.press('Enter')
  ]);

  const firstRow = page.locator('tbody tr').filter({ hasText: freshAssetCode }).first();
  await expect(firstRow).toBeVisible();
  const assetCode = await firstRow.locator('td').nth(1).textContent();

  expect(assetCode).toBeTruthy();

  // Open Actions menu
  await firstRow.getByRole('button', { name: /Actions/i }).click();
  
  // Click Delete
  await page.getByRole('menuitem', { name: /Delete/i }).click();

  // Confirm deletion (assuming a confirmation dialog appears)
  const confirmDialog = page.getByRole('alertdialog').or(page.getByRole('dialog', { name: /Confirm/i }));
  if (await confirmDialog.isVisible({ timeout: 10000 })) {
    await Promise.all([
      page.waitForResponse(response => 
        response.url().includes('/api/assets') && 
        response.request().method() === 'DELETE' && 
        response.status() === 200
      ),
      page.waitForResponse(response => 
        response.url().includes('/api/assets') && 
        response.request().method() === 'GET' && 
        response.status() === 200
      ),
      confirmDialog.getByRole('button', { name: /Delete|Confirm/i }).click()
    ]);
  }

  // Wait for item to disappear or show success toast
  // We'll search for it and expect no results
  // Should show "No results." or the row should be missing
  await expect(page.locator('tbody')).not.toContainText(assetCode!);
});
