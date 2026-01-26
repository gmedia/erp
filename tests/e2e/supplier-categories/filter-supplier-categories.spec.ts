import { test, expect } from '@playwright/test';
import { createSupplierCategory, searchSupplierCategory } from '../helpers';

test('filter supplier categories end-to-end', async ({ page }) => {
  const name = await createSupplierCategory(page);

  await searchSupplierCategory(page, name);

  await expect(page.locator(`text=${name}`)).toBeVisible();

  // Filter by non-existent name
  const nonExistentName = 'NonExistentCategory' + Date.now();
  await page.fill('input[placeholder="Search supplier categories..."]', nonExistentName);
  await page.press('input[placeholder="Search supplier categories..."]', 'Enter');

  // Verify the original item is not visible
  await expect(page.locator(`text=${name}`)).not.toBeVisible();
  
  // Verify empty state message
  await expect(page.getByText(/No results/i)).toBeVisible();
});
