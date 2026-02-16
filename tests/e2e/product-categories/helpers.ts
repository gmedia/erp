import { Page, expect } from '@playwright/test';

/**
 * Create a new product category via the UI.
 * Assumes page is already on /product-categories (handled by test.beforeEach).
 */
export async function createProductCategory(page: Page): Promise<string> {
    const timestamp = Date.now();
    const name = `ProdCat ${Math.random().toString(36).substring(2, 7)}${timestamp}`;

    const addButton = page.getByRole('button', { name: /Add/i });
    await expect(addButton).toBeVisible();
    await addButton.click();

    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();

    await dialog.locator('input[name="name"]').fill(name);
    await dialog.locator('textarea[name="description"]').fill('Test Description');

    const submitButton = dialog.getByRole('button', { name: /Add/i });
    await submitButton.click();

    await expect(dialog).not.toBeVisible({ timeout: 10000 });

    return name;
}

/**
 * Search for a product category by name.
 */
export async function searchProductCategory(page: Page, name: string): Promise<void> {
    const searchInput = page.getByPlaceholder('Search product categories...');
    await searchInput.fill(name);
    await searchInput.press('Enter');
    await page.waitForResponse(
        r => r.url().includes('/api/product-categories') && r.status() < 400
    ).catch(() => null);
}

/**
 * Edit an existing product category via the Actions dropdown.
 */
export async function editProductCategory(
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
    if (updates.description) {
        await dialog.locator('textarea[name="description"]').fill(updates.description);
    }

    const updateBtn = dialog.getByRole('button', { name: /Update/i });
    await updateBtn.click();

    await expect(dialog).not.toBeVisible({ timeout: 10000 });
}
