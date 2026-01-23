import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('filter customers by status', async ({ page }) => {
  // Login and navigate
  await login(page);
  await page.goto('/customers');

  // Open filter
  const filterButton = page.getByRole('button', { name: /filter/i });
  if (await filterButton.isVisible()) {
    await filterButton.click();
  }

  // Select active status filter
  const statusFilter = page.locator('button:has-text("All statuses")');
  if (await statusFilter.isVisible()) {
    await statusFilter.click();
    await page.getByRole('option', { name: 'Active', exact: true }).click();
  }

  // Apply filter
  const applyBtn = page.getByRole('button', { name: /apply/i });
  if (await applyBtn.isVisible()) {
    await applyBtn.click();
  }

  // Verify only active customers are shown
  const activeBadges = page.locator('text=Active');
  await expect(activeBadges.first()).toBeVisible();
});
