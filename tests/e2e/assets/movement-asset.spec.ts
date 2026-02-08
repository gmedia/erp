import { test, expect } from '@playwright/test';
import { login, createAsset } from '../helpers';

test('record asset movement end-to-end', async ({ page }) => {
  // Create a fresh asset
  const assetCode = await createAsset(page);

  await page.goto('/assets');
  await page.waitForLoadState('networkidle');

  // Search for the asset
  const searchInput = page.getByPlaceholder(/Search assets.../i);
  await searchInput.fill(assetCode);
  await searchInput.press('Enter');
  await page.waitForLoadState('networkidle');

  // Go to the asset's profile
  const firstRow = page.locator('tbody tr').filter({ hasText: assetCode }).first();
  await expect(firstRow).toBeVisible({ timeout: 10000 });
  await firstRow.locator('td').nth(2).click();
  
  await page.waitForURL(/\/assets\/\w+/);
  await page.waitForLoadState('networkidle');
  // Target specific summary card elements
  await expect(page.getByText('Current Location & PIC')).toBeVisible({ timeout: 15000 });
  const branchLabel = page.getByTestId('asset-branch');
  const initialBranch = await branchLabel.textContent();

  // Click "Move/Assign" button
  const moveBtn = page.getByRole('button', { name: /Move\/Assign/i });
  await expect(moveBtn).toBeVisible();
  await moveBtn.click();
  
  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  // Wait for "Destination Details" to appear (Transfer is default)
  await expect(dialog.locator('h4:has-text("Destination Details")')).toBeVisible({ timeout: 10000 });

  // Select Target Branch - It should currently show the asset's current branch (e.g. "Head Office")
  const branchTrigger = dialog.locator('button').filter({ hasText: /Head Office|Select destination branch/i }).first();
  await expect(branchTrigger).toBeVisible();
  await branchTrigger.click();
  
  // Wait for popover and search input
  const popoverSearchInput = page.getByPlaceholder('Search...').filter({ visible: true }).last();
  await expect(popoverSearchInput).toBeVisible({ timeout: 10000 });
  await popoverSearchInput.fill('Branch'); 
  
  // Wait for options to load
  const branchOption = page.getByRole('option').first();
  await expect(branchOption).toBeVisible({ timeout: 10000 });
  const targetBranchName = await branchOption.textContent();
  await branchOption.click();

  // Select Target Location
  const locationTrigger = dialog.locator('button').filter({ hasText: /Select destination location/i }).first();
  await expect(locationTrigger).toBeVisible();
  await locationTrigger.click();
  
  const locationOption = page.getByRole('option').first();
  await expect(locationOption).toBeVisible({ timeout: 10000 });
  await locationOption.click();

  // Fill date (default is today, so just ensure it's there)
  await dialog.locator('input[name="reference"]').fill('E2E-TRANS-001');

  // Submit
  const submitBtn = dialog.getByRole('button', { name: /Record Movement/i });
  await expect(submitBtn).toBeVisible();
  await submitBtn.click();

  // Wait for success toast and dialog to close
  await expect(page.getByText(/Movement recorded successfully/i)).toBeVisible({ timeout: 15000 });
  await expect(dialog).not.toBeVisible({ timeout: 10000 });

  // Verify Summary reflects the new branch
  if (targetBranchName && targetBranchName !== initialBranch) {
    await expect(branchLabel).toContainText(targetBranchName.trim(), { timeout: 10000 });
  }

  // Verify entry in Movements tab
  await page.getByRole('tab', { name: 'Movements' }).click();
  await expect(page.locator('table')).toContainText('transfer');
  await expect(page.locator('table')).toContainText('E2E-TRANS-001');
});
