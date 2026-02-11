import { test, expect } from '@playwright/test';
import { createAssetCategory, deleteAssetCategory } from '../helpers';

test('delete existing asset category end-to-end', async ({ page }) => {
  const code = await createAssetCategory(page, {
    name: 'Category to Delete',
  });

  await deleteAssetCategory(page, code);

  await page.fill('input[placeholder="Search asset categories..."]', code);
  await page.press('input[placeholder="Search asset categories..."]', 'Enter');
  
  // Use more consistent "No results." text based on DataTable implementation
  await expect(page.getByText('No results.')).toBeVisible();
});
