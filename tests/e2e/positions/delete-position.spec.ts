import { test, expect } from '@playwright/test';
import { createPosition, searchPosition, login } from '../helpers';

test.describe('Position Delete E2E Test', () => {
  test('should delete an existing position successfully', async ({ page }) => {
    const name = await createPosition(page);
    await searchPosition(page, name);

    const row = page.locator('tr', { hasText: name }).first();
    await expect(row).toBeVisible();

    const actionsBtn = row.getByRole('button', { name: /Actions/i });
    await expect(actionsBtn).toBeVisible();
    await actionsBtn.click();

    const deleteItem = page.getByRole('menuitem', { name: /Delete/i });
    await expect(deleteItem).toBeVisible();
    await deleteItem.click();

    const confirmBtn = page.getByRole('button', { name: /Delete/i }).filter({ visible: true });
    await expect(confirmBtn).toBeVisible();
    await confirmBtn.click();

    await page.waitForResponse(resp => resp.url().includes('/api/positions') && resp.status() === 204);
    await expect(page.locator(`text=${name}`)).not.toBeVisible();
  });
});
