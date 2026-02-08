import { test, expect } from '@playwright/test';
import { login, createAsset } from '../helpers';

test('edit existing asset end-to-end', async ({ page }) => {  
  // Create a fresh asset to edit
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
      response.status() === 200
    ),
    searchInput.press('Enter')
  ]);

  const firstRow = page.locator('tbody tr').filter({ hasText: freshAssetCode }).first();
  await expect(firstRow).toBeVisible();
  const assetCode = await firstRow.locator('td').nth(1).textContent();
  const assetName = await firstRow.locator('td').nth(2).textContent();

  expect(assetCode).toBeTruthy();
  expect(assetName).toBeTruthy();

  // Open Actions menu
  await firstRow.getByRole('button', { name: /Actions/i }).click();
  await page.getByRole('menuitem', { name: /Edit/i }).click();

  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  // Update name
  const updatedName = `${assetName} Updated`;
  await dialog.locator('input[name="name"]').fill(updatedName);

  // Submit
  const updateBtn = dialog.getByRole('button', { name: /Update/i });
  await updateBtn.click();

  // Wait for dialog to disappear (with error check)
  try {
    await expect(dialog).not.toBeVisible({ timeout: 5000 });
  } catch (error) {
    // If it fails, check for validation errors
    const errors = await dialog.locator('.text-destructive, .text-red-500').allTextContents();
    console.log('Validation errors found:', errors);
    throw new Error(`Dialog did not close. Possible validation errors: ${errors.join(', ')}`);
  }

  // Verify update in the table
  searchInput = page.getByPlaceholder(/Search assets.../i);
  await searchInput.fill(assetCode!);
  await searchInput.press('Enter');
  
  await expect(page.locator('tbody tr').first()).toContainText(updatedName);
});
