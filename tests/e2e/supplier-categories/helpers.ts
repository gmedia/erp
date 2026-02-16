import { Page, expect } from '@playwright/test';

/**
 * Create a new supplier category via the UI.
 * Assumes page is already on /supplier-categories (handled by test.beforeEach).
 */
export async function createSupplierCategory(page: Page): Promise<string> {
    const timestamp = Date.now();
    const name = `${Math.random().toString(36).substring(2, 7)}${timestamp}`;

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
 * Search for a supplier category by name.
 */
export async function searchSupplierCategory(page: Page, name: string): Promise<void> {
    const searchInput = page.getByPlaceholder('Search supplier categories...');
    await searchInput.fill(name);
    await searchInput.press('Enter');
    await page.waitForResponse(
        r => r.url().includes('/api/supplier-categories') && r.status() < 400
    ).catch(() => null);
}

/**
 * Edit an existing supplier category via the Actions dropdown.
 */
export async function editSupplierCategory(
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
