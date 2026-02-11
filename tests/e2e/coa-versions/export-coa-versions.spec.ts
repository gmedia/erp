import { test, expect } from '@playwright/test';
import { login, createCoaVersion } from '../helpers';

test('export coa versions end‑to‑end', async ({ page }) => {
  // 1️⃣ Create data to ensure export is not empty (optional but good)
  await createCoaVersion(page);

  // 2️⃣ Click the Export button
  const exportButton = page.getByRole('button', { name: /Export/i });
  await expect(exportButton).toBeVisible();

  // 3️⃣ Trigger the download and wait for it
  const downloadPromise = page.waitForEvent('download');
  await exportButton.click();
  const download = await downloadPromise;

  // 4️⃣ Verify the download was successful
  expect(download.suggestedFilename()).toMatch(/^coa_versions.*\.xlsx$/);
  
  // Optional: Check content type if needed, but filename check is usually enough for E2E
});
