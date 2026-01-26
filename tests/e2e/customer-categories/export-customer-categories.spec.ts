import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('export customer categories end-to-end', async ({ page }) => {
  await login(page);
  await page.goto('/customer-categories');

  // Trigger export
  const downloadPromise = page.waitForEvent('download');
  await page.getByRole('button', { name: /export/i }).click();
  const download = await downloadPromise;

  expect(download.suggestedFilename()).toMatch(/customer_categories_export_.*\.xlsx/);
});
