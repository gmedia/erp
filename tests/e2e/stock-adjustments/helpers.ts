import { Page, expect } from '@playwright/test';

import { searchAndWaitForApi } from '../helpers';

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

interface AsyncOptionCandidate {
    readonly searchText: string;
    readonly optionText: string;
}

function escapeRegExp(value: string): string {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
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

export async function createStockAdjustment(page: Page): Promise<string> {
    const adjustmentNumber = `SA-E2E-${Date.now()}-${Math.random().toString(36).slice(2, 8).toUpperCase()}`;
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

    const dialog = page.getByRole('dialog', {
        name: /Add New Stock Adjustment/i,
    });
    await expect(dialog).toBeVisible();

    await dialog
        .locator('input[name="adjustment_number"]')
        .fill(adjustmentNumber);
    const createdRow = page
        .getByText(new RegExp(`^${escapeRegExp(adjustmentNumber)}$`, 'i'))
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

    await itemDialog.locator('input[name="quantity_adjusted"]').fill('2');

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

            // Re-select product/unit in case previous async-select choice did not persist.
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
            await itemDialog.locator('input[name="quantity_adjusted"]').fill('2');
            await page.waitForTimeout(250);
        }
    }

    expect(itemCommitted, 'Stock adjustment item row should be committed before submit').toBeTruthy();

    // Wait until the item is committed to the parent form, then close item dialog deterministically.
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

    // Ensure item append has been committed before submitting the main form.
    const emptyItemsCell = dialog.getByText('No items added yet.');
    if (await emptyItemsCell.isVisible().catch(() => false)) {
        await expect(emptyItemsCell).not.toBeVisible({ timeout: 10000 });
    }
    await expect(dialog.locator('tbody tr')).toHaveCount(1, { timeout: 10000 });

    // Close any lingering AsyncSelect popover that may intercept the submit click.
    if (await page.locator('[role="listbox"]:visible, ul[aria-busy]:visible').count()) {
        await page.keyboard.press('Escape').catch(() => null);
    }

    let createResponseStatus: number | null = null;
    let lastCreateError: unknown;

    for (let attempt = 1; attempt <= 3; attempt++) {
        const submitButton = dialog.getByRole('button', { name: 'Add', exact: true });
        await expect(submitButton).toBeVisible();
        await expect(submitButton).toBeEnabled();

        const createResponsePromise = page.waitForResponse(
            (r) =>
                r.url().includes('/api/stock-adjustments') &&
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

            if (attempt === 3) {
                throw error;
            }

            // Retry when submit click did not produce a reliable creation signal.
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
            : new Error('Stock adjustment create response was not captured.');
    }

    expect(createResponseStatus, 'Stock adjustment create response should be successful').toBeLessThan(400);

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
    if (!await createdRow.isVisible().catch(() => false)) {
        await searchStockAdjustment(page, adjustmentNumber);
    }
    await expect(createdRow).toBeVisible({
        timeout: 30000,
    });

    return adjustmentNumber;
}

export async function searchStockAdjustment(
    page: Page,
    identifier: string,
): Promise<void> {
    await searchAndWaitForApi(
        page,
        page.getByPlaceholder('Search stock adjustments...'),
        identifier,
        '/api/stock-adjustments',
    );
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
    );

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
        ;

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
