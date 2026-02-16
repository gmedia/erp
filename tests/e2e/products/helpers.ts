import { Page, expect } from '@playwright/test';

/**
 * Create a new product via the UI.
 * NOTE: Assumes page is already on /products (handled by test.beforeEach).
 */
export async function createProduct(
    page: Page,
    overrides: Partial<{
        code: string;
        name: string;
        type: string;
        category_id: string;
        unit_id: string;
        branch_id: string;
        cost: string;
        selling_price: string;
        billing_model: string;
        status: string;
    }> = {}
): Promise<string> {
    const timestamp = Date.now();
    const productCode = overrides.code ?? `PRD-${timestamp}`;

    const addButton = page.getByRole('button', { name: /Add/i });
    await expect(addButton).toBeVisible();
    await addButton.click();

    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();

    // 1. General Info
    await dialog.locator('input[name="code"]').fill(productCode);
    await dialog.locator('input[name="name"]').fill(overrides.name ?? `Product ${timestamp}`);

    // 2. Type Select
    const typeTrigger = dialog.locator('label', { hasText: /Type/i }).locator('..').getByRole('combobox');
    await typeTrigger.click();
    await page.getByRole('option', { name: overrides.type ?? 'Finished Good', exact: true }).click();

    // 3. Category (Async Select)
    const categoryTrigger = dialog.locator('button[role="combobox"]').filter({ hasText: /Select category/i });
    await categoryTrigger.click();
    const catSearch = page.getByPlaceholder('Search...').filter({ visible: true }).last();
    const catName = overrides.category_id ?? 'Electronics';
    if (await catSearch.isVisible()) {
        await catSearch.fill(catName);
        await page.waitForResponse(r => r.url().includes('/api/product-categories') && r.status() < 400).catch(() => null);
    }
    await page.locator('[role="option"]').filter({ hasText: new RegExp(`^${catName}$`) }).first().click({ force: true });

    // 4. Unit (Async Select)
    const unitTrigger = dialog.locator('button[role="combobox"]').filter({ hasText: /Select unit/i });
    await unitTrigger.click();
    const unitSearch = page.getByPlaceholder('Search...').filter({ visible: true }).last();
    const unitName = overrides.unit_id ?? 'Piece';
    if (await unitSearch.isVisible()) {
        await unitSearch.fill(unitName);
        await page.waitForResponse(r => r.url().includes('/api/units') && r.status() < 400).catch(() => null);
    }
    await page.locator('[role="option"]').filter({ hasText: new RegExp(`^${unitName}$`) }).first().click({ force: true });

    // 5. Pricing
    await dialog.locator('input[name="cost"]').fill(overrides.cost ?? '1000');
    await dialog.locator('input[name="selling_price"]').fill(overrides.selling_price ?? '1500');

    // 6. Config
    const billingTrigger = dialog.locator('label', { hasText: /^Billing Model$/i }).locator('..').getByRole('combobox');
    await billingTrigger.click();
    await page.getByRole('option', { name: overrides.billing_model ?? 'One Time' }).click();

    // 7. Status
    const statusTrigger = dialog.locator('label', { hasText: /^Status$/ }).locator('..').getByRole('combobox');
    await statusTrigger.click();
    await page.getByRole('option', { name: overrides.status ?? 'Active', exact: true }).click();

    // 8. Submit
    const submitButton = dialog.locator('button[type="submit"]');
    await submitButton.click();

    await expect(dialog).not.toBeVisible({ timeout: 15000 });

    return productCode;
}

/**
 * Search for a product by code or name.
 */
export async function searchProduct(page: Page, query: string): Promise<void> {
    const searchInput = page.getByPlaceholder('Search code, name...');
    await searchInput.fill(query);
    await searchInput.press('Enter');
    await page.waitForResponse(
        r => r.url().includes('/api/products') && r.status() < 400
    ).catch(() => null);
}

/**
 * Edit an existing product via the Actions dropdown.
 */
export async function editProduct(
    page: Page,
    _productCode: string,
    updates: { name?: string; selling_price?: string; status?: string }
): Promise<void> {
    const firstRow = page.locator('tbody tr').first();
    await firstRow.getByRole('button').last().click();

    await page.getByRole('menuitem', { name: /Edit/i }).click();

    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();

    if (updates.name) {
        await dialog.locator('input[name="name"]').fill(updates.name);
    }
    if (updates.selling_price) {
        await dialog.locator('input[name="selling_price"]').fill(updates.selling_price);
    }
    if (updates.status) {
        const statusTrigger = dialog.locator('label', { hasText: /^Status$/ }).locator('..').getByRole('combobox');
        await statusTrigger.click();
        await page.getByRole('option', { name: updates.status, exact: true }).click();
    }

    const updateBtn = dialog.getByRole('button', { name: /Update/i });
    await updateBtn.click();

    await expect(dialog).not.toBeVisible({ timeout: 15000 });
}
