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
  await page.getByPlaceholder('Search...').last().fill('IT Equipment');
  await page.getByRole('option', { name: 'IT Equipment' }).first().click();

  // Select Branch (AsyncSelect)
  await dialog.locator('button').filter({ hasText: /Select a branch/i }).click();
  await page.getByPlaceholder('Search...').last().fill('Head Office');
  await page.getByRole('option', { name: 'Head Office' }).first().click();

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
});
