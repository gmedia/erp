import { Page, expect } from '@playwright/test';

async function selectAsyncOption(
    page: Page,
    trigger: ReturnType<Page['getByRole']>,
    searchText: string,
    optionText: string,
): Promise<void> {
    for (let attempt = 0; attempt < 3; attempt++) {
        await expect(trigger).toBeVisible();
        await trigger.click();

        const listbox = page.locator('[role="listbox"][aria-busy="false"]').first();
        await expect(listbox).toBeVisible();
        const container = listbox.locator('..');

        if (searchText) {
            const searchInput = container.getByPlaceholder('Search...');
            await expect(searchInput).toBeVisible();
            await searchInput.fill(searchText);
        }

        const option = listbox.getByRole('option', { name: new RegExp(optionText, 'i') }).first();

        try {
            await expect(option).toBeVisible();
            await option.click({ force: true });
            await expect(listbox).toBeHidden({ timeout: 5000 }).catch(() => null);
            return;
        } catch (e) {
            if (attempt === 2) throw e;
            await page.waitForTimeout(200);
        }
    }
}

export async function createInventoryStocktake(page: Page): Promise<string> {
    const stocktakeNumber = `SO-E2E-${Date.now()}`;

    const addButton = page.getByRole('button', { name: /Add/i });
    await expect(addButton).toBeVisible();
    await addButton.click();

    const dialog = page.getByRole('dialog', { name: /Add New Inventory Stocktake/i });
    await expect(dialog).toBeVisible();

    await dialog.locator('input[name="stocktake_number"]').fill(stocktakeNumber);

    await selectAsyncOption(
        page,
        dialog.getByRole('combobox', { name: /Warehouse/i }),
        'Main',
        'Main Warehouse',
    );

    await dialog.getByRole('button', { name: /Add Item/i }).click();
    const itemDialog = page.getByRole('dialog', { name: /Add Item/i });
    await expect(itemDialog).toBeVisible();

    await selectAsyncOption(
        page,
        itemDialog.getByRole('combobox', { name: /Product/i }),
        '',
        '.+',
    );
    await selectAsyncOption(
        page,
        itemDialog.getByRole('combobox', { name: /Unit/i }),
        '',
        '.+',
    );

    await itemDialog.locator('input[name="system_quantity"]').fill('10');
    await itemDialog.locator('input[name="counted_quantity"]').fill('11');
    await itemDialog.getByRole('button', { name: /Save Item/i }).click();
    await expect(itemDialog).not.toBeVisible({ timeout: 10000 });

    const submitButton = dialog.getByRole('button', { name: 'Add', exact: true });
    const createResponse = page
        .waitForResponse(
            (r) =>
                r.url().includes('/api/inventory-stocktakes') &&
                r.request().method() === 'POST' &&
                r.status() < 400,
            { timeout: 45000 },
        )
        .catch(() => null);

    await submitButton.click();
    await createResponse;

    try {
        await expect(dialog).not.toBeVisible({ timeout: 5000 });
    } catch {
        const cancelButton = dialog.getByRole('button', { name: /(cancel|batal)/i });
        await page.keyboard.press('Escape').catch(() => null);
        if (await cancelButton.isVisible().catch(() => false)) {
            await cancelButton.click({ force: true });
        } else {
            await page.keyboard.press('Escape').catch(() => null);
        }
    }

    await expect(dialog).not.toBeVisible({ timeout: 15000 });
    await expect(page.getByText(stocktakeNumber, { exact: true }).first()).toBeVisible({
        timeout: 30000,
    });

    return stocktakeNumber;
}

export async function searchInventoryStocktake(page: Page, identifier: string): Promise<void> {
    const searchInput = page.getByPlaceholder('Search inventory stocktakes...');
    await searchInput.fill(identifier);
    await searchInput.press('Enter');
    await page.waitForResponse(
        (r) => r.url().includes('/api/inventory-stocktakes') && r.status() < 400,
    ).catch(() => null);
}

export async function editInventoryStocktake(
    page: Page,
    _identifier: string,
    updates: Record<string, string>,
): Promise<void> {
    const firstRow = page.locator('tbody tr').first();
    await firstRow.getByRole('button').last().click();

    await page.getByRole('menuitem', { name: 'Edit' }).click();

    // Wait for the GET detail request to complete before editing
    const detailResponse = page.waitForResponse(
        (r) => r.url().match(/\/api\/inventory-stocktakes\/\d+$/) && r.request().method() === 'GET',
        { timeout: 15000 },
    ).catch(() => null);

    const dialog = page.getByRole('dialog', { name: /Edit Inventory Stocktake/i });
    await expect(dialog).toBeVisible();

    await detailResponse;

    if (updates.stocktake_number) {
        await dialog.locator('input[name="stocktake_number"]').fill(updates.stocktake_number);
    }

    const updateBtn = dialog.getByRole('button', { name: /Update/i });
    const updateResponse = page
        .waitForResponse(
            (r) =>
                r.url().includes('/api/inventory-stocktakes/') &&
                r.request().method() === 'PUT' &&
                r.status() < 400,
            { timeout: 45000 },
        )
        .catch(() => null);

    await updateBtn.click();
    await updateResponse;

    try {
        await expect(dialog).not.toBeVisible({ timeout: 5000 });
    } catch {
        const cancelButton = dialog.getByRole('button', { name: /(cancel|batal)/i });
        await page.keyboard.press('Escape').catch(() => null);
        if (await cancelButton.isVisible().catch(() => false)) {
            await cancelButton.click({ force: true });
        } else {
            await page.keyboard.press('Escape').catch(() => null);
        }
    }

    await expect(dialog).not.toBeVisible({ timeout: 15000 });
}
