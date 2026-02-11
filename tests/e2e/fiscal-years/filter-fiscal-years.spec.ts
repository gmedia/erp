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

  // Open filter modal
  const filterBtn = page.getByRole('button', { name: /Filters/i });
  await filterBtn.click();
  
  // Select status in modal
  // Note: Select trigger often has the placeholder or current value.
  // We look for the label "Status" and then the trigger below/next to it.
  // Or assuming standard order, it likely has "Status" label.
  // The 'Status' select trigger in shadcn/ui usually has role 'combobox'.
  const statusTrigger = page.locator('button[role="combobox"]').filter({ hasText: /All Statuses|Status/i }).first();
  await statusTrigger.click();
  
  await page.getByRole('option', { name: 'Open', exact: true }).click();
  
  // Click "Apply Filters"
  await page.getByRole('button', { name: /Apply Filters/i }).click();
  
  // Wait for table update
  await page.waitForLoadState('networkidle');

  // Verify results
  await expect(page.locator('tr', { hasText: openName })).toBeVisible();
  
  // Ensure closed one is NOT visible (or at least check that rows don't contain it)
  await expect(page.locator('tr', { hasText: closedName })).not.toBeVisible();
});
