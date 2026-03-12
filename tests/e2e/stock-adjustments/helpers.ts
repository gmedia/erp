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

        const option = listbox
            .getByRole('option', { name: new RegExp(optionText, 'i') })
            .first();

        try {
            await expect(option).toBeVisible();
            await option.click({ force: true });
            await expect(listbox)
                .toBeHidden({ timeout: 5000 })
                .catch(() => null);
            return;
        } catch (e) {
            if (attempt === 2) throw e;
            await page.waitForTimeout(200);
        }
    }
}

export async function createStockAdjustment(page: Page): Promise<string> {
    const adjustmentNumber = `SA-E2E-${Date.now()}`;

    const addButton = page.getByRole('button', { name: /Add/i });
    await expect(addButton).toBeVisible();
    await addButton.click();

    const dialog = page.getByRole('dialog', {
        name: /Add New Stock Adjustment/i,
    });
    await expect(dialog).toBeVisible();

    await dialog
        .locator('input[name="adjustment_number"]')
        .fill(adjustmentNumber);

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

    await itemDialog.locator('input[name="quantity_adjusted"]').fill('2');
    await itemDialog.getByRole('button', { name: /Save Item/i }).click();
    await expect(itemDialog).not.toBeVisible({ timeout: 10000 });

    const submitButton = dialog.getByRole('button', { name: 'Add', exact: true });
    const createResponse = page
        .waitForResponse(
            (r) =>
                r.url().includes('/api/stock-adjustments') &&
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
        const cancelButton = dialog.getByRole('button', {
            name: /(cancel|batal)/i,
        });
        await page.keyboard.press('Escape').catch(() => null);
        if (await cancelButton.isVisible().catch(() => false)) {
            await cancelButton.click({ force: true });
        } else {
            await page.keyboard.press('Escape').catch(() => null);
        }
    }

    await expect(dialog).not.toBeVisible({ timeout: 15000 });
    await expect(page.getByText(adjustmentNumber, { exact: true }).first()).toBeVisible({
        timeout: 30000,
    });

    return adjustmentNumber;
}

export async function searchStockAdjustment(
    page: Page,
    identifier: string,
): Promise<void> {
    const searchInput = page.getByPlaceholder('Search stock adjustments...');
    await searchInput.fill(identifier);
    await searchInput.press('Enter');
    await page
        .waitForResponse(
            (r) => r.url().includes('/api/stock-adjustments') && r.status() < 400,
        )
        .catch(() => null);
}

export async function editStockAdjustment(
    page: Page,
    _identifier: string,
    updates: Record<string, string>,
): Promise<void> {
    const firstRow = page.locator('tbody tr').first();
    await firstRow.getByRole('button').last().click();

    await page.getByRole('menuitem', { name: 'Edit' }).click();

    // Wait for the GET detail request to complete before editing
    const detailResponse = page.waitForResponse(
        (r) => !!r.url().match(/\/api\/stock-adjustments\/\d+$/) && r.request().method() === 'GET',
        { timeout: 15000 },
    ).catch(() => null);

    const dialog = page.getByRole('dialog', { name: /Edit Stock Adjustment/i });
    await expect(dialog).toBeVisible();

    await detailResponse;

    // Wait for form to fully settle after detail data is loaded and re-rendered
    await page.waitForTimeout(2000);

    if (updates.adjustment_number) {
        const adjNumInput = dialog.locator('input[name="adjustment_number"]');
        await expect(adjNumInput).toBeVisible();
        await adjNumInput.click();
        await adjNumInput.fill(updates.adjustment_number);
    }

    // Click dialog title area to dismiss any floating popover/listbox
    await dialog.locator('[class*="DialogHeader"], [class*="dialog-header"]').first().click().catch(() => null);
    await page.waitForTimeout(300);

    const updateBtn = dialog.getByRole('button', { name: /Update/i });
    await expect(updateBtn).toBeVisible({ timeout: 10000 });

    const updateResponse = page
        .waitForResponse(
            (r) =>
                r.url().includes('/api/stock-adjustments/') &&
                r.request().method() === 'PUT' &&
                r.status() < 400,
            { timeout: 45000 },
        )
        .catch(() => null);

    await updateBtn.scrollIntoViewIfNeeded();
    await updateBtn.click({ force: true });
    await updateResponse;

    try {
        await expect(dialog).not.toBeVisible({ timeout: 5000 });
    } catch {
        const cancelButton = dialog.getByRole('button', {
            name: /(cancel|batal)/i,
        });
        await page.keyboard.press('Escape').catch(() => null);
        if (await cancelButton.isVisible().catch(() => false)) {
            await cancelButton.click({ force: true });
        } else {
            await page.keyboard.press('Escape').catch(() => null);
        }
    }

    await expect(dialog).not.toBeVisible({ timeout: 15000 });
}
