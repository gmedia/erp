import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('export coa versions end‑to‑end', async ({ page }) => {
  // 1️⃣ Login and navigate to coa versions page
  await login(page);
  await page.goto('/coa-versions');

  // 2️⃣ Click the Export button
  const exportButton = page.getByRole('button', { name: /Export/i });
  await expect(exportButton).toBeVisible();

  // 3️⃣ Trigger the download and wait for it
  const downloadPromise = page.waitForEvent('download');
  await exportButton.click();
  const download = await downloadPromise;

  // 4️⃣ Verify the download was successful
  expect(download.suggestedFilename()).toContain('coa_versions');
  expect(download.suggestedFilename()).toContain('.xlsx');
});
