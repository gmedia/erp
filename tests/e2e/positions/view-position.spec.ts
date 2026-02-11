import { test, expect } from '@playwright/test';
import { createPosition, searchPosition } from '../helpers';

test.describe('Position View E2E Test', () => {
  test('should view position details successfully', async ({ page }) => {
    const name = await createPosition(page);
    await searchPosition(page, name);

    const row = page.locator('tr').filter({ hasText: name }).first();
    await expect(row).toBeVisible();

    const actionsBtn = row.getByRole('button', { name: /Actions/i });
    await actionsBtn.click();

    const viewItem = page.getByRole('menuitem', { name: /View/i });
    await expect(viewItem).toBeVisible();
    await viewItem.click();

    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();
    await expect(dialog).toContainText('View Position');
    await expect(dialog).toContainText(name);

    const footer = dialog.locator('[data-slot="dialog-footer"]');
    const closeBtn = footer.getByRole('button', { name: /Close/i });
    await expect(closeBtn).toBeVisible();
    await closeBtn.click();
    await expect(dialog).not.toBeVisible();
  });
});
