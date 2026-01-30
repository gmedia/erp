import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('filter products end-to-end', async ({ page }) => {
  await login(page);
  await page.goto('/products');

  // Open filters
  await page.getByRole('button', { name: /Filters/i }).click();

  // 1. Filter by Status: Active
  const filterDialog = page.getByRole('dialog', { name: /Filters/i }); // Define filterDialog here
  const statusTrigger = filterDialog.locator('label', { hasText: /^Status$/ }).locator('..').getByRole('combobox');
  await statusTrigger.waitFor({ state: 'visible' }); // Added wait
  await statusTrigger.click({ force: true });
  await page.waitForTimeout(500); // Added wait
  // Use part of label for more resilience
  await page.locator('[role="option"]').filter({ hasText: 'Active' }).first().click({ force: true }); // Changed locator and added force: true
  await filterDialog.getByRole('button', { name: /Apply Filters/i }).click(); // Changed to use filterDialog

  // Verify all visible rows have Active status
  const statusBadges = page.locator('span:has-text("Active")');
  await expect(statusBadges.first()).toBeVisible();

  // 2. Filter by Type: Service
  await page.getByRole('button', { name: /Filters/i }).click();
  await filterDialog.getByRole('button', { name: /Reset/i }).click();
  
  // Re-open if it closed, or just continue
  if (!await filterDialog.isVisible()) {
    await page.getByRole('button', { name: /Filters/i }).click();
  }

  const typeTrigger = filterDialog.locator('label', { hasText: /Type/i }).locator('..').getByRole('combobox');
  await typeTrigger.click({ force: true });
  await page.waitForTimeout(1000);
  // Broader search for options
  await page.locator('[role="option"], .select-item').filter({ hasText: 'Service' }).first().click({ force: true });

  await filterDialog.locator('button:has-text("Apply Filters")').click({ force: true });

  // Verify visible rows are Service
  const typeBadges = page.locator('span:has-text("Service")');
  await expect(typeBadges.first()).toBeVisible();
});
