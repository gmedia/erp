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
            return;
        } catch (e) {
            if (attempt === 2) throw e;
            await page.waitForTimeout(200);
        }
    }
}

export async function createStockTransfer(page: Page): Promise<string> {
    const transferNumber = `ST-E2E-${Date.now()}`;

    const addButton = page.getByRole('button', { name: /Add/i });
    await expect(addButton).toBeVisible();
    await addButton.click();

    const dialog = page.getByRole('dialog', { name: /Add New Stock Transfer/i });
    await expect(dialog).toBeVisible();

    await dialog.locator('input[name="transfer_number"]').fill(transferNumber);

    await selectAsyncOption(
        page,
        dialog.getByRole('combobox', { name: /From Warehouse/i }),
        'Main',
        'Main Warehouse',
    );
    await selectAsyncOption(
        page,
        dialog.getByRole('combobox', { name: /To Warehouse/i }),
        'Transit',
        'Transit Warehouse',
    );

    const firstRow = dialog.locator('tbody tr').first();
    const productTrigger = firstRow.getByRole('combobox', { name: /Product/i });
    await expect(productTrigger).toBeVisible();
    await productTrigger.click();
    const productListbox = page.locator('[role="listbox"][aria-busy="false"]').first();
    await expect(productListbox).toBeVisible();
    await expect(productListbox.getByRole('option').first()).toBeVisible();
    await productListbox.getByRole('option').first().click();

    const unitTrigger = firstRow.getByRole('combobox', { name: /Unit/i });
    await expect(unitTrigger).toBeVisible();
    await unitTrigger.click();
    const unitListbox = page.locator('[role="listbox"][aria-busy="false"]').first();
    await expect(unitListbox).toBeVisible();
    await expect(unitListbox.getByRole('option').first()).toBeVisible();
    await unitListbox.getByRole('option').first().click();

    await dialog.locator('input[name="items.0.quantity"]').fill('2');

    const submitButton = dialog.getByRole('button', { name: 'Add', exact: true });
    await submitButton.click();

    await expect(dialog).not.toBeVisible({ timeout: 15000 });

    return transferNumber;
}

export async function searchStockTransfer(page: Page, identifier: string): Promise<void> {
    const searchInput = page.getByPlaceholder('Search stock transfers...');
    await searchInput.fill(identifier);
    await searchInput.press('Enter');
    await page.waitForResponse(
        (r) => r.url().includes('/api/stock-transfers') && r.status() < 400,
    ).catch(() => null);
}

export async function editStockTransfer(
    page: Page,
    _identifier: string,
    updates: Record<string, string>,
): Promise<void> {
    const firstRow = page.locator('tbody tr').first();
    await firstRow.getByRole('button').last().click();

    await page.getByRole('menuitem', { name: 'Edit' }).click();

    const dialog = page.getByRole('dialog', { name: /Edit Stock Transfer/i });
    await expect(dialog).toBeVisible();

    if (updates.transfer_number) {
        await dialog.locator('input[name="transfer_number"]').fill(updates.transfer_number);
    }

    const updateBtn = dialog.getByRole('button', { name: /Update/i });
    await updateBtn.click();

    await expect(dialog).not.toBeVisible({ timeout: 15000 });
}
