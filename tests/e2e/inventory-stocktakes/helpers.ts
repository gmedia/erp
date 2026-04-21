import { Page, expect } from '@playwright/test';

import { searchAndWaitForApi } from '../helpers';

interface AsyncOptionCandidate {
    readonly searchText: string;
    readonly optionText: string;
}

function escapeRegExp(value: string): string {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

async function selectAsyncOption(
    page: Page,
    trigger: ReturnType<Page['getByRole']>,
    searchText: string,
    optionText: string,
): Promise<void> {
    for (let attempt = 0; attempt < 3; attempt++) {
        await expect(trigger).toBeVisible();
        await trigger.click();

        const listbox = page.locator('[role="listbox"]:visible, ul[aria-busy]:visible').last();
        await expect(listbox).toBeVisible();

        if (searchText) {
            const searchInput = page.locator('input[placeholder="Search..."]:visible').last();
            if (await searchInput.isVisible().catch(() => false)) {
                await searchInput.fill(searchText);
                await page.waitForTimeout(250);
            }
        }

        const option = listbox
            .locator('button')
            .filter({ hasText: new RegExp(optionText, 'i') })
            .first();

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

async function selectOptionWithCandidates(
    page: Page,
    trigger: ReturnType<Page['getByRole']>,
    candidates: readonly AsyncOptionCandidate[],
    fallback: AsyncOptionCandidate,
): Promise<void> {
    for (const candidate of candidates) {
        try {
            await selectAsyncOption(
                page,
                trigger,
                candidate.searchText,
                candidate.optionText,
            );
            return;
        } catch {
            // Try next candidate until one is available in current dataset.
        }
    }

    await selectAsyncOption(
        page,
        trigger,
        fallback.searchText,
        fallback.optionText,
    );
}

export async function createInventoryStocktake(page: Page): Promise<string> {
    const stocktakeNumber = `SO-E2E-${Date.now()}-${Math.random().toString(36).slice(2, 8).toUpperCase()}`;
    const productCandidates: readonly AsyncOptionCandidate[] = [
        { searchText: 'MDF Wood Panel', optionText: 'MDF Wood Panel' },
        { searchText: 'Executive Office Desk', optionText: 'Executive Office Desk' },
        { searchText: 'Wooden Table Legs', optionText: 'Wooden Table Legs' },
        { searchText: 'Inventory Sample Product', optionText: 'Inventory Sample Product' },
    ];
    const unitCandidates: readonly AsyncOptionCandidate[] = [
        { searchText: 'Sheet', optionText: 'Sheet' },
        { searchText: 'pcs', optionText: 'pcs' },
        { searchText: 'Set', optionText: 'Set' },
        { searchText: 'Box', optionText: 'Box' },
    ];

    const addButton = page.getByRole('button', { name: /Add/i });
    await expect(addButton).toBeVisible();
    await addButton.click();

    const dialog = page.getByRole('dialog', { name: /Add New Inventory Stocktake/i });
    await expect(dialog).toBeVisible();

    await dialog.locator('input[name="stocktake_number"]').fill(stocktakeNumber);
    const createdRow = page
        .getByText(new RegExp(`^${escapeRegExp(stocktakeNumber)}$`, 'i'))
        .first();

    await selectAsyncOption(
        page,
        dialog.getByRole('combobox', { name: /Warehouse/i }),
        'Main',
        'Main Warehouse',
    );

    await dialog.getByRole('button', { name: /Add Item/i }).click();
    const itemDialog = page.getByRole('dialog', { name: /Add Item/i });
    await expect(itemDialog).toBeVisible();

    await selectOptionWithCandidates(
        page,
        itemDialog.getByRole('combobox', { name: /Product/i }),
        productCandidates,
        { searchText: '', optionText: '.+' },
    );
    await selectOptionWithCandidates(
        page,
        itemDialog.getByRole('combobox', { name: /Unit/i }),
        unitCandidates,
        { searchText: '', optionText: '.+' },
    );

    await itemDialog.locator('input[name="system_quantity"]').fill('10');
    await itemDialog.locator('input[name="counted_quantity"]').fill('11');
    const parentRows = dialog.locator('tbody tr');
    const saveItemButton = itemDialog.getByRole('button', { name: /Save Item/i });
    let itemCommitted = false;

    for (let attempt = 1; attempt <= 3; attempt++) {
        await saveItemButton.click({ force: true });

        try {
            await expect
                .poll(async () => parentRows.count(), { timeout: 10000 })
                .toBeGreaterThan(0);
            itemCommitted = true;
            break;
        } catch (error) {
            if (attempt === 3) {
                throw error;
            }

            await selectOptionWithCandidates(
                page,
                itemDialog.getByRole('combobox', { name: /Product/i }),
                productCandidates,
                { searchText: '', optionText: '.+' },
            );
            await selectOptionWithCandidates(
                page,
                itemDialog.getByRole('combobox', { name: /Unit/i }),
                unitCandidates,
                { searchText: '', optionText: '.+' },
            );
            await itemDialog.locator('input[name="system_quantity"]').fill('10');
            await itemDialog.locator('input[name="counted_quantity"]').fill('11');
            await page.waitForTimeout(250);
        }
    }

    expect(itemCommitted, 'Inventory stocktake item row should be committed before submit').toBeTruthy();

    for (let closeAttempt = 1; closeAttempt <= 3; closeAttempt++) {
        if (!await itemDialog.isVisible().catch(() => false)) {
            break;
        }

        const closeItemDialogButton = itemDialog
            .getByRole('button', { name: /(close|cancel|batal)/i })
            .first();
        if (await closeItemDialogButton.isVisible().catch(() => false)) {
            await closeItemDialogButton.click({ force: true });
        } else {
            await page.keyboard.press('Escape').catch(() => null);
        }
    }
    await expect(page.getByRole('dialog', { name: /Add Item/i })).toHaveCount(0, { timeout: 10000 });

    const emptyItemsCell = dialog.getByText('No items added yet.');
    if (await emptyItemsCell.isVisible().catch(() => false)) {
        await expect(emptyItemsCell).not.toBeVisible({ timeout: 10000 });
    }
    await expect(dialog.locator('tbody tr')).toHaveCount(1, { timeout: 10000 });

    if (await page.locator('[role="listbox"]:visible, ul[aria-busy]:visible').count()) {
        await page.keyboard.press('Escape').catch(() => null);
    }

    let createResponseStatus: number | null = null;
    let lastCreateError: unknown;

    for (let attempt = 1; attempt <= 3; attempt++) {
        if (!await dialog.isVisible().catch(() => false)) {
            if (!await createdRow.isVisible().catch(() => false)) {
                await searchInventoryStocktake(page, stocktakeNumber).catch(() => null);
            }
            if (await createdRow.isVisible().catch(() => false)) {
                createResponseStatus = 200;
                break;
            }
            if (attempt === 3) {
                throw new Error('Inventory stocktake dialog closed before creation could be confirmed.');
            }
            continue;
        }

        const submitButton = dialog.getByRole('button', { name: 'Add', exact: true });
        await expect(submitButton).toBeVisible({ timeout: 10000 });
        await expect(submitButton).toBeEnabled();

        const createResponsePromise = page.waitForResponse(
            (r) =>
                r.url().includes('/api/inventory-stocktakes') &&
                r.request().method() === 'POST',
            { timeout: 10000 },
        ).then((response) => ({ type: 'response' as const, status: response.status() }));
        const createdRowPromise = createdRow
            .waitFor({ state: 'visible', timeout: 10000 })
            .then(() => ({ type: 'row' as const }));

        await submitButton.click({ force: true });

        try {
            const creationSignal = await Promise.any([
                createResponsePromise,
                createdRowPromise,
            ]);
            if (creationSignal.type === 'response') {
                createResponseStatus = creationSignal.status;
            } else {
                createResponseStatus = 200;
            }
            break;
        } catch (error) {
            lastCreateError = error;

            if (await createdRow.isVisible().catch(() => false)) {
                createResponseStatus = 200;
                break;
            }

            if (!await dialog.isVisible().catch(() => false)) {
                await searchInventoryStocktake(page, stocktakeNumber).catch(() => null);
                if (await createdRow.isVisible().catch(() => false)) {
                    createResponseStatus = 200;
                    break;
                }
            }

            if (attempt === 3) {
                throw error;
            }

            await page.keyboard.press('Escape').catch(() => null);
            if (await page.locator('[role="listbox"]:visible, ul[aria-busy]:visible').count()) {
                await page.keyboard.press('Escape').catch(() => null);
            }

            const lingeringItemDialog = page.getByRole('dialog', { name: /Add Item/i });
            if (await lingeringItemDialog.isVisible().catch(() => false)) {
                await page.keyboard.press('Escape').catch(() => null);
                await expect(lingeringItemDialog).toBeHidden({ timeout: 3000 }).catch(() => null);
            }
        }
    }

    if (createResponseStatus === null) {
        throw lastCreateError instanceof Error
            ? lastCreateError
            : new Error('Inventory stocktake create response was not captured.');
    }

    expect(createResponseStatus, 'Inventory stocktake create response should be successful').toBeLessThan(400);

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
    if (!await createdRow.isVisible().catch(() => false)) {
        await searchInventoryStocktake(page, stocktakeNumber);
    }
    await expect(createdRow).toBeVisible({
        timeout: 30000,
    });

    return stocktakeNumber;
}

export async function searchInventoryStocktake(page: Page, identifier: string): Promise<void> {
    await searchAndWaitForApi(
        page,
        page.getByPlaceholder('Search inventory stocktakes...'),
        identifier,
        '/api/inventory-stocktakes',
    );
}

export async function editInventoryStocktake(
    page: Page,
    identifier: string,
    updates: Record<string, string>,
): Promise<void> {
    const detailResponse = page.waitForResponse(
        (r) => !!r.url().match(/\/api\/inventory-stocktakes\/\d+$/) && r.request().method() === 'GET',
        { timeout: 15000 },
    );

    const row = page.locator('tbody tr').filter({ hasText: identifier }).first();
    await expect(row).toBeVisible({ timeout: 15000 });
    await row.getByRole('button').last().click();

    const dialog = page.getByRole('dialog', { name: /Edit Inventory Stocktake/i });
    await Promise.all([
        detailResponse,
        expect(dialog).toBeVisible(),
        page.getByRole('menuitem', { name: 'Edit' }).click(),
    ]);


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
        ;

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
