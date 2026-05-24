import { Locator, Page, expect } from '@playwright/test';
import { searchAndWaitForApi } from '../helpers';

export function getReconcilableRow(page: Page): Locator {
    return page
        .locator('tbody tr')
        .filter({ hasNotText: /completed/i })
        .first();
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

export async function createBankReconciliation(page: Page): Promise<string> {
    const addButton = page.getByRole('button', { name: /Add/i });
    await expect(addButton).toBeVisible();
    await addButton.click();

    const dialog = page.getByRole('dialog', { name: /Add New Bank Reconciliation/i });
    await expect(dialog).toBeVisible();

    const accountTrigger = dialog.getByRole('combobox', { name: /Bank Account/i });
    await selectAsyncOption(page, accountTrigger, 'Cash', 'Cash');

    const fiscalYearTrigger = dialog.getByRole('combobox', { name: /Fiscal Year/i });
    await selectAsyncOption(page, fiscalYearTrigger, '', '.');

    const statementBalance = dialog.locator('input[name="statement_balance"]');
    await statementBalance.fill('100000');

    const submitBtn = dialog.getByRole('button', { name: /^Add$/i });
    await expect(submitBtn).toBeVisible();
    await submitBtn.click();

    await expect(dialog).not.toBeVisible({ timeout: 15000 });

    const row = page.locator('tbody tr').first();
    await expect(row).toBeVisible();
    const accountCell = row.locator('td').nth(1);
    const identifier = (await accountCell.textContent()) || 'Cash';

    return identifier.trim().split('\n')[0].trim();
}

export async function searchBankReconciliation(page: Page, query: string): Promise<void> {
    await searchAndWaitForApi(
        page,
        page.getByPlaceholder(/Search/i),
        query,
        '/api/bank-reconciliations',
    );
}

export async function editBankReconciliation(
    page: Page,
    identifier: string,
    updates: Record<string, string>,
): Promise<void> {
    await searchBankReconciliation(page, identifier);

    const row = page.locator('tbody tr').filter({ hasText: identifier }).first();
    await expect(row).toBeVisible();

    await row.getByRole('button').last().click();
    await page.getByRole('menuitem', { name: 'Edit' }).click();

    const dialog = page.getByRole('dialog').first();
    await expect(dialog).toBeVisible();

    await page.waitForResponse(
        (r) => r.url().includes('/api/bank-reconciliations/') && r.request().method() === 'GET',
        { timeout: 15000 },
    );

    if (updates.statement_balance) {
        await dialog.locator('input[name="statement_balance"]').fill(updates.statement_balance);
    }

    const updateBtn = dialog.getByRole('button', { name: /Update/i });
    const updateResponse = page.waitForResponse(
        (r) =>
            r.url().includes('/api/bank-reconciliations/') &&
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
