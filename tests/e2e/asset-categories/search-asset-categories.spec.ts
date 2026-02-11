import { test, expect } from '@playwright/test';
import { createAssetCategory, searchAssetCategory, login } from '../helpers';

test.describe('Asset Categories Search', () => {
  test('search asset category by code', async ({ page }) => {
    const code = await createAssetCategory(page, {
        name: 'Search Test Category By Code',
    });

    await searchAssetCategory(page, code);

    const row = page.locator('tr', { hasText: code });
    await expect(row).toBeVisible();
    await expect(row).toContainText(code);
    await expect(row).toContainText('Search Test Category By Code');
  });

  test('search asset category by name', async ({ page }) => {
    const timestamp = Date.now();
    const name = `Unique Category ${timestamp}`;
    const code = await createAssetCategory(page, {
        name: name,
    });

    await searchAssetCategory(page, name);

    const row = page.locator('tr', { hasText: name });
    await expect(row).toBeVisible();
    await expect(row).toContainText(code);
    await expect(row).toContainText(name);
  });

  test('search returns no results for non-existent category', async ({ page }) => {
    await login(page);
    await page.goto('/asset-categories');
    
    const nonExistent = 'NON_EXISTENT_CATEGORY_BLAH_BLAH';
    await page.fill('input[placeholder="Search asset categories..."]', nonExistent);
    await page.press('input[placeholder="Search asset categories..."]', 'Enter');

    await expect(page.locator('tr', { hasText: nonExistent })).not.toBeVisible();
    await expect(page.getByText('No results.')).toBeVisible();
  });
});
