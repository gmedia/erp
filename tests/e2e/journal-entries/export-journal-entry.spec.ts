import { test, expect } from '@playwright/test';
import { createJournalEntry, login } from '../helpers';
import * as fs from 'fs';
import * as path from 'path';

test('export journal entries works correctly', async ({ page }) => {
  test.setTimeout(120000);
  await login(page);
  await createJournalEntry(page);
  
  // Ensure we are on the page
  await page.goto('/journal-entries');

  const exportBtn = page.getByRole('button', { name: /Export/i });
  await expect(exportBtn).toBeVisible();

  const [download] = await Promise.all([
    page.waitForEvent('download'),
    exportBtn.click(),
  ]);

  const downloadsDir = path.resolve('e2e/test-results', 'downloads');
  if (!fs.existsSync(downloadsDir)) {
    fs.mkdirSync(downloadsDir, { recursive: true });
  }
  const destPath = path.join(downloadsDir, download.suggestedFilename());
  await download.saveAs(destPath);

  expect(download.suggestedFilename()).toMatch(/journal_entries_export_.*\.xlsx$/i);
  expect(fs.existsSync(destPath)).toBeTruthy();
});
