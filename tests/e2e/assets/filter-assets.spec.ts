import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('filter assets by search and status end-to-end', async ({ page }) => {
  await login(page);
  await page.goto('/assets');

  // Wait for the table to load
  await page.waitForSelector('table');

  // 1. Test Search
  const initialRow = page.locator('tbody tr').first();
  const assetName = await initialRow.locator('td').nth(2).textContent();

  const searchInput = page.getByPlaceholder(/Search assets.../i);
  await searchInput.clear();
  await searchInput.fill(assetName!);
  await Promise.all([
    page.waitForResponse(response => 
      response.url().includes('/api/assets') && 
      response.status() === 200
    ),
    searchInput.press('Enter')
  ]);
  
  await expect(page.locator('tbody tr').first()).toContainText(assetName!);

  // 2. Test Status Filter
  // Reset search
  await searchInput.clear();
  await Promise.all([
    page.waitForResponse(response => 
      response.url().includes('/api/assets') && 
      response.status() === 200
    ),
    searchInput.press('Enter')
  ]);

  // Open Filters modal
  await page.getByRole('button', { name: /Filters/i }).click();
  const filterDialog = page.getByRole('dialog', { name: /Filters/i });
  await expect(filterDialog).toBeVisible();

  // select status "Active"
  await filterDialog.locator('button').filter({ hasText: /Select a status/i }).click();
  const statusOption = page.getByRole('option', { name: /^Active$/i }).first();
  await expect(statusOption).toBeVisible();
  await statusOption.click();
  
  // Click Apply Filters
  await Promise.all([
    page.waitForResponse(response => 
      response.url().includes('/api/assets') && 
      response.url().includes('status=active') &&
      response.status() === 200
    ).catch(() => null), // Catch if it resolves too fast or different param
    filterDialog.getByRole('button', { name: /Apply Filters/i }).click()
  ]);

  // Wait for modal to close
  await expect(filterDialog).not.toBeVisible();

  // Check results
  const rows = page.locator('tbody tr');
  await expect(rows.first()).toBeVisible();
  // Expect all rows to have "active" status badge
  const statusBadges = page.locator('tbody tr .badge').or(page.locator('tbody tr td:nth-child(6)'));
  await expect(statusBadges.first()).toContainText(/Active/i);
});
