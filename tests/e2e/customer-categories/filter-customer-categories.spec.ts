import { test, expect } from '@playwright/test';
import { createCustomerCategory, searchCustomerCategory } from '../helpers';

test('filter customer categories end-to-end', async ({ page }) => {
  const name = await createCustomerCategory(page);

  await searchCustomerCategory(page, name);

  await expect(page.locator(`text=${name}`)).toBeVisible();

  // Filter by non-existent name
  const nonExistentName = 'NonExistentCategory' + Date.now();
  await page.fill('input[placeholder="Search customer categories..."]', nonExistentName);
  await page.press('input[placeholder="Search customer categories..."]', 'Enter');

  // Verify the original item is not visible
  await expect(page.locator(`text=${name}`)).not.toBeVisible();
  
  // Verify empty state message
  await expect(page.getByText(/No results/i)).toBeVisible();
});
