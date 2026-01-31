import { test, expect } from '@playwright/test';
import { createFiscalYear, login } from '../helpers';

test('filter fiscal years by status', async ({ page }) => {
  const timestamp = Date.now();
  const openName = `FilterOpen-${timestamp}`;
  const closedName = `FilterClosed-${timestamp}`;

  // Ensure we have at least one open and one closed FY
  await createFiscalYear(page, { name: openName, status: 'Open' });
  await createFiscalYear(page, { name: closedName, status: 'Closed' });

  await page.goto('/fiscal-years');

  // Open filter (assuming there's a filter button/popover)
  const filterBtn = page.getByRole('button', { name: /Filters/i });
  await filterBtn.click();

  // Select Status: Open in filter dialog/popover
  const statusTrigger = page.getByRole('combobox').filter({ hasText: /All Statuses|Status/i });
  await statusTrigger.click();
  await page.getByRole('option', { name: 'Open', exact: true }).click();

  // Apply filter
  const applyBtn = page.getByRole('button', { name: /Apply Filters/i });
  await applyBtn.click();

  // Verify results
  await expect(page.locator('tr', { hasText: openName })).toBeVisible();
  await expect(page.locator('tr', { hasText: openName })).toContainText('open');
  await expect(page.locator('tr', { hasText: closedName })).not.toBeVisible();
});
