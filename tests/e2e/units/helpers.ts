import { Page, expect } from '@playwright/test';

/**
 * Create a new unit via the UI.
 * NOTE: Assumes page is already on /units (handled by test.beforeEach).
 */
export async function createUnit(page: Page): Promise<string> {
    const timestamp = Date.now();
    const name = `Unit ${Math.random().toString(36).substring(2, 7)}${timestamp}`;

    const addButton = page.getByRole('button', { name: /Add/i });
    await expect(addButton).toBeVisible();
    await addButton.click();

    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();

    await dialog.locator('input[name="name"]').fill(name);
    await dialog.locator('input[name="symbol"]').fill('kg');

    const submitButton = dialog.getByRole('button', { name: /Add/i });
    await submitButton.click();

    await expect(dialog).not.toBeVisible({ timeout: 10000 });

    return name;
}

/**
 * Search for a unit by name.
 */
export async function searchUnit(page: Page, name: string): Promise<void> {
    const searchInput = page.getByPlaceholder('Search units...');
    await searchInput.fill(name);
    await searchInput.press('Enter');
    await page.waitForResponse(
        r => r.url().includes('/api/units') && r.status() < 400
    ).catch(() => null);
}

/**
 * Edit an existing unit via the UI dropdown.
 */
export async function editUnit(
    page: Page,
    _identifier: string,
    updates: { name?: string; symbol?: string }
): Promise<void> {
    // Locate the row and open the Actions menu
    const firstRow = page.locator('tbody tr').first();
    await expect(firstRow).toBeVisible();
    await firstRow.getByRole('button').last().click();

    // Click the Edit menu item
    await page.getByRole('menuitem', { name: /Edit/i }).click();

    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();

    // Update fields if provided
    if (updates.name) {
        await dialog.locator('input[name="name"]').fill(updates.name);
    }
    if (updates.symbol) {
        await dialog.locator('input[name="symbol"]').fill(updates.symbol);
    }

    // Submit the edit dialog
    const updateBtn = dialog.getByRole('button', { name: /Update/i });
    await updateBtn.click();

    await expect(dialog).not.toBeVisible({ timeout: 10000 });
}
