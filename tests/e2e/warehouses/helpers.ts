import { Page, expect } from '@playwright/test';

export async function createWarehouse(page: Page): Promise<string> {
    const timestamp = Date.now();
    const name = `Warehouse ${timestamp}`;
    const code = `WH-${timestamp}`;

    const addButton = page.getByRole('button', { name: /Add/i });
    await expect(addButton).toBeVisible();
    await addButton.click();

    const dialog = page.getByRole('dialog', { name: /Add/i });
    await expect(dialog).toBeVisible();

    await dialog.locator('input[name="code"]').fill(code);
    await dialog.locator('input[name="name"]').fill(name);

    const submitButton = dialog.getByRole('button', { name: /Add/i });
    await expect(submitButton).toBeVisible();

    await dialog.getByRole('combobox', { name: /Branch/i }).click();
    const branchOption = page.locator('[role="option"]:visible, ul[aria-busy]:visible button:visible').first();
    await expect(branchOption).toBeVisible({ timeout: 10000 });
    await branchOption.click({ force: true });

    const responsePromise = page.waitForResponse(
        (r) => r.url().includes('/api/warehouses') && r.status() < 400,
    );

    await submitButton.click();
    await responsePromise;

    await expect(dialog).not.toBeVisible({ timeout: 15000 });

    return name;
}

export async function searchWarehouse(page: Page, name: string): Promise<void> {
    const searchInput = page.getByPlaceholder('Search warehouses...');
    const responsePromise = page.waitForResponse(
        r => r.url().includes('/api/warehouses') && r.status() < 400
    );
    await searchInput.clear();
    await searchInput.fill(name);
    await searchInput.press('Enter');
    await responsePromise;
}

export async function editWarehouse(
    page: Page,
    identifier: string,
    updates: Record<string, string>
): Promise<void> {
    const firstRow = page.locator('tbody tr').filter({ hasText: identifier }).first();
    await expect(firstRow).toBeVisible();
    await firstRow.getByRole('button').last().click();

    await page.getByRole('menuitem', { name: 'Edit' }).click();

    const dialog = page.getByRole('dialog', { name: /Edit/i });
    await expect(dialog).toBeVisible();

    if (updates.code) {
        await dialog.locator('input[name="code"]').fill(updates.code);
    }
    if (updates.name) {
        await dialog.locator('input[name="name"]').fill(updates.name);
    }

    const updateBtn = dialog.getByRole('button', { name: /Update/i });
    await expect(updateBtn).toBeVisible();

    const responsePromise = page.waitForResponse(
        (r) => r.url().includes('/api/warehouses') && r.status() < 400,
    );

    await updateBtn.click();
    await responsePromise;

    await expect(dialog).not.toBeVisible({ timeout: 15000 });
}
