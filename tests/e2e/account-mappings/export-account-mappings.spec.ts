import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('export account mappings end‑to‑end', async ({ page }) => {
  test.setTimeout(120000);

  await login(page);
  await page.goto('/account-mappings');

  const exportButton = page.getByRole('button', { name: /Export/i });
  await expect(exportButton).toBeVisible({ timeout: 15000 });

  const downloadPromise = page.waitForEvent('download');
  await exportButton.click();
  const download = await downloadPromise;

  expect(download.suggestedFilename()).toContain('account_mappings');
  expect(download.suggestedFilename()).toContain('.xlsx');
});

