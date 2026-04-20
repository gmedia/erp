import { randomUUID } from 'node:crypto';
import { Page, expect } from '@playwright/test';

/**
 * Create a new position via the UI.
 * NOTE: Assumes page is already on /positions (handled by test.beforeEach).
 */
export async function createPosition(page: Page): Promise<string> {
    const timestamp = Date.now();
    const name = `${randomUUID().slice(0, 5)}${timestamp}`;

    const addButton = page.getByRole('button', { name: /Add/i });
    await expect(addButton).toBeVisible();
    await addButton.click();

    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();

    await dialog.locator('input[name="name"]').fill(name);

    const submitButton = dialog.getByRole('button', { name: /Add/i });
    await submitButton.click();

    await expect(dialog).not.toBeVisible({ timeout: 10000 });

    return name;
}

/**
 * Search for a position by name.
 */
export async function searchPosition(page: Page, name: string): Promise<void> {
    const searchInput = page.getByPlaceholder('Search positions...');
    const responsePromise = page.waitForResponse(
        r => r.url().includes('/api/positions') && r.status() < 400
    );
    await searchInput.clear();
    await searchInput.fill(name);
    await searchInput.press('Enter');
    await responsePromise;
}

/**
 * Edit an existing position via the Actions dropdown.
 */
export async function editPosition(
    page: Page,
    _identifier: string,
    updates: Record<string, string>
): Promise<void> {
    const firstRow = page.locator('tbody tr').first();
    await firstRow.getByRole('button').last().click();

    await page.getByRole('menuitem', { name: 'Edit' }).click();

    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();

    if (updates.name) {
        await dialog.locator('input[name="name"]').fill(updates.name);
    }

    const updateBtn = dialog.getByRole('button', { name: /Update/i });
    await updateBtn.click();

    await expect(dialog).not.toBeVisible({ timeout: 10000 });
}
