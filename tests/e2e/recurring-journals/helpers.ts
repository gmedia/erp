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

        const popoverList = page.locator('ul[aria-busy]:visible').last();
        await expect(popoverList).toBeVisible({ timeout: 5000 });

        const searchInput = page.locator('input[placeholder="Search..."]:visible').last();
        if (searchText && await searchInput.isVisible().catch(() => false)) {
            await searchInput.fill(searchText);
            await page.waitForTimeout(800);
        } else {
            await page.waitForTimeout(500);
        }

        const option = popoverList
            .locator('button')
            .filter({ hasText: new RegExp(optionText, 'i') })
            .first();

        try {
            await expect(option).toBeVisible({ timeout: 5000 });
            await option.click({ force: true });
            await page.waitForTimeout(300);
            return;
        } catch (e) {
            if (attempt === 2) throw e;
            await page.keyboard.press('Escape').catch(() => null);
            await page.waitForTimeout(300);
        }
    }
}

export async function createRecurringJournal(page: Page): Promise<string> {
    const timestamp = Date.now();
    const random = Math.random().toString(36).slice(2, 8).toUpperCase();
    const name = `RJ-E2E-${timestamp}-${random}`;

    const addButton = page.getByRole('button', { name: /Add/i });
    await expect(addButton).toBeVisible();
    await addButton.click();

    const dialog = page.getByRole('dialog', { name: /Add New Recurring Journal/i });
    await expect(dialog).toBeVisible();

    await dialog.locator('input[name="name"]').fill(name);

    const frequencyTrigger = dialog.getByRole('combobox', { name: /Frequency/i });
    if (await frequencyTrigger.isVisible().catch(() => false)) {
        await frequencyTrigger.click();
        const monthlyOption = page
            .locator('[role="option"]:visible')
            .filter({ hasText: /Monthly/i })
            .first();
        await expect(monthlyOption).toBeVisible();
        await monthlyOption.click({ force: true });
    }

    await dialog.locator('input[name="description_template"]').fill(`Auto ${name}`);

    const addLineBtn = dialog.getByRole('button', { name: /Add Line/i });
    await addLineBtn.scrollIntoViewIfNeeded();
    await expect(addLineBtn).toBeVisible();

    await addLineBtn.click();
    await page.waitForTimeout(500);
    const lineDialog = page.locator('[role="dialog"]').last();
    await expect(lineDialog).toBeVisible({ timeout: 5000 });

    const accountTrigger = lineDialog.locator('button[role="combobox"]').first();
    await selectAsyncOption(page, accountTrigger, 'Cash', 'Cash');

    await lineDialog.locator('input[name="debit"]').fill('5000');
    await lineDialog.locator('input[name="credit"]').fill('0');

    const saveLineBtn = lineDialog.getByRole('button', { name: /Save Line/i });
    await saveLineBtn.click();
    await expect(lineDialog).not.toBeVisible({ timeout: 5000 });

    await addLineBtn.scrollIntoViewIfNeeded();
    await addLineBtn.click();
    await page.waitForTimeout(500);
    const lineDialog2 = page.locator('[role="dialog"]').last();
    await expect(lineDialog2).toBeVisible({ timeout: 5000 });

    const accountTrigger2 = lineDialog2.locator('button[role="combobox"]').first();
    await selectAsyncOption(page, accountTrigger2, 'Revenue', 'Revenue');

    await lineDialog2.locator('input[name="debit"]').fill('0');
    await lineDialog2.locator('input[name="credit"]').fill('5000');

    const saveLineBtn2 = lineDialog2.getByRole('button', { name: /Save Line/i });
    await saveLineBtn2.click();
    await expect(lineDialog2).not.toBeVisible({ timeout: 5000 });

    const submitBtn = dialog.getByRole('button', { name: /^Add$/i });
    await expect(submitBtn).toBeVisible();
    await submitBtn.click();

    await expect(dialog).not.toBeVisible({ timeout: 15000 });
    return name;
}

export async function searchRecurringJournal(page: Page, query: string): Promise<void> {
    await searchAndWaitForApi(
        page,
        page.getByPlaceholder(/Search name, description/i),
        query,
        '/api/recurring-journals',
    );
}

export async function editRecurringJournal(
    page: Page,
    identifier: string,
    updates: Record<string, string>,
): Promise<void> {
    await searchRecurringJournal(page, identifier);

    const row = page.locator('tbody tr').filter({ hasText: identifier }).first();
    await expect(row).toBeVisible();

    await row.getByRole('button').last().click();
    await page.getByRole('menuitem', { name: 'Edit' }).click();

    const dialog = page.getByRole('dialog').first();
    await expect(dialog).toBeVisible();

    await page.waitForResponse(
        (r) => r.url().includes('/api/recurring-journals/') && r.request().method() === 'GET',
        { timeout: 15000 },
    );

    if (updates.name) {
        await dialog.locator('input[name="name"]').fill(updates.name);
    }

    const updateBtn = dialog.getByRole('button', { name: /Update/i });
    const updateResponse = page.waitForResponse(
        (r) =>
            r.url().includes('/api/recurring-journals/') &&
            r.request().method() === 'PUT' &&
            r.status() < 400,
        { timeout: 30000 },
    );

    await updateBtn.click();
    await updateResponse;

    try {
        await expect(dialog).not.toBeVisible({ timeout: 5000 });
    } catch {
        await page.keyboard.press('Escape').catch(() => null);
    }

    await expect(dialog).not.toBeVisible({ timeout: 15000 });
}
