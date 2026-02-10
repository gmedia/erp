import { test, expect } from '@playwright/test';
import { createPosition, login } from '../helpers';

test.describe('Position Filter E2E Test', () => {
  test('should filter positions by search in filter popover successfully', async ({ page }) => {
    const name = await createPosition(page);
    await login(page);
    await page.goto('/positions');
    await page.waitForLoadState('networkidle');

    // Open filter popover
    const filterBtn = page.getByRole('button', { name: /Filter/i });
    await filterBtn.click();
    await page.waitForTimeout(500); // Small delay for popover animation

    // Fill search in filter popover
    // Using a more specific locator to avoid ambiguity with the main search bar
    const popoverSearchInput = page.locator('[role="dialog"], [data-radix-popper-content-wrapper]').getByPlaceholder('Search positions...').last();
    await expect(popoverSearchInput).toBeVisible();
    await popoverSearchInput.fill(name);
    
    // The filter should apply automatically or we might need to click outside/Enter
    // In this UI it usually applies on input or has an Apply button.
    // Based on createSimpleEntityFilterFields, it's an Input.
    
    await page.waitForLoadState('networkidle');
    await page.waitForSelector(`text=${name}`);
    await expect(page.locator('tr').filter({ hasText: name }).first()).toBeVisible();
  });
});
