import { test, expect } from '@playwright/test';
import { createJournalEntry, login } from '../helpers';
import * as fs from 'fs';
import * as path from 'path';

test('export journal entries works correctly', async ({ page }) => {
  test.setTimeout(120000);
  await login(page);
  await createJournalEntry(page);
  
  await page.goto('/journal-entries');
  await page.waitForLoadState('networkidle');

  const exportBtn = page.getByRole('button', { name: /Export/i });
  await expect(exportBtn).toBeVisible();

  const [download] = await Promise.all([
    page.waitForEvent('download'),
    exportBtn.click(),
  ]);

  const downloadsDir = path.resolve('test-results/downloads');
  if (!fs.existsSync(downloadsDir)) {
    fs.mkdirSync(downloadsDir, { recursive: true });
  }
  const destPath = path.join(downloadsDir, download.suggestedFilename());
  await download.saveAs(destPath);

  expect(download.suggestedFilename()).toMatch(/journal_entries_export_.*\.xlsx$/i);
  expect(fs.existsSync(destPath)).toBeTruthy();
  
  // Clean up
  if (fs.existsSync(destPath)) {
    fs.unlinkSync(destPath);
  }
});
