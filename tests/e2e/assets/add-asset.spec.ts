import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('add new asset end-to-end', async ({ page }) => {
  await login(page);
  await page.goto('/assets');

  // Open the "Add Asset" dialog
  await page.getByRole('button', { name: /Add/i }).click();
  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  // Fill required fields
  const timestamp = Date.now();
  const assetCode = `AST-${timestamp}`;
  const assetName = `Test Asset ${timestamp}`;

  await dialog.locator('input[name="asset_code"]').fill(assetCode);
  await dialog.locator('input[name="name"]').fill(assetName);

  // Select Category (AsyncSelect)
  await dialog.locator('button').filter({ hasText: /Select a category/i }).click();
  const categorySearchInput = page.getByPlaceholder('Search...').last();
  await expect(categorySearchInput).toBeVisible();
  await categorySearchInput.fill('IT Equipment');
  const categoryOption = page.getByRole('option', { name: /^IT Equipment$/i }).first();
  await expect(categoryOption).toBeVisible();
  await categoryOption.click();

  // Select Branch (AsyncSelect)
  await dialog.locator('button').filter({ hasText: /Select a branch/i }).click();
  const branchSearchInput = page.getByPlaceholder('Search...').last();
  await expect(branchSearchInput).toBeVisible();
  await branchSearchInput.fill('Head Office');
  const branchOption = page.getByRole('option', { name: /^Head Office$/i }).first();
  await expect(branchOption).toBeVisible();
  await branchOption.click();

  // Purchase Information (using default purchase_date: today)
  await dialog.locator('input[name="purchase_cost"]').fill('1500000');

  // Status (Select)
  await dialog.locator('button').filter({ hasText: /Draft/i }).click();
  await page.getByRole('option', { name: 'Active' }).click();

  // Submit
  const submitBtn = dialog.getByRole('button', { name: /Add/i }).last();
  await submitBtn.click();

  // Wait for dialog to disappear (confirms successful submission)
  await expect(dialog).not.toBeVisible({ timeout: 10000 });

  // Verify it appears in the table
  const searchInput = page.getByPlaceholder(/Search assets.../i);
  await searchInput.clear();
  await searchInput.fill(assetCode);
  await Promise.all([
    page.waitForResponse(response => 
      response.url().includes('/api/assets') && 
      response.url().includes(encodeURIComponent(assetCode)) &&
      response.status() === 200
    ),
    searchInput.press('Enter')
  ]);
  await expect(page.locator(`text=${assetCode}`)).toBeVisible();

  // Go to profile and verify movement
  await page.locator('tbody tr').filter({ hasText: assetCode }).first().locator('td').nth(2).click();
  await page.waitForURL(/\/assets\/\w+/);
  await page.getByRole('tab', { name: 'Movements' }).click();
  await expect(page.locator('table')).toContainText('acquired');
});
