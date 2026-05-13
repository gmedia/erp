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

export async function createPeriodClosing(page: Page): Promise<string> {
    const addButton = page.getByRole('button', { name: /Add/i });
    await expect(addButton).toBeVisible();
    await addButton.click();

    const dialog = page.getByRole('dialog', { name: /Add New Period Closing/i });
    await expect(dialog).toBeVisible();

    const fiscalYearTrigger = dialog.getByRole('combobox', { name: /Fiscal Year/i });
    await selectAsyncOption(page, fiscalYearTrigger, '', '.');

    const closingTypeTrigger = dialog.getByRole('combobox', { name: /Closing Type/i });
    if (await closingTypeTrigger.isVisible().catch(() => false)) {
        await closingTypeTrigger.click();
        const monthlyOption = page
            .locator('[role="option"]:visible')
            .filter({ hasText: /Monthly/i })
            .first();
        await expect(monthlyOption).toBeVisible();
        await monthlyOption.click({ force: true });
    }

    const periodMonth = dialog.locator('input[name="period_month"]');
    await periodMonth.clear();
    await periodMonth.fill(String(Math.floor(Math.random() * 12) + 1));

    const periodYear = dialog.locator('input[name="period_year"]');
    await periodYear.clear();
    await periodYear.fill('2026');

    const retainedEarningsTrigger = dialog.getByRole('combobox', { name: /Retained Earnings Account/i });
    await selectAsyncOption(page, retainedEarningsTrigger, '', '.');

    const submitBtn = dialog.getByRole('button', { name: /^Add$/i });
    await expect(submitBtn).toBeVisible();
    await submitBtn.click();

    await expect(dialog).not.toBeVisible({ timeout: 15000 });

    const row = page.locator('tbody tr').first();
    await expect(row).toBeVisible();
    const periodMonthCell = row.locator('td').nth(2);
    const periodYearCell = row.locator('td').nth(3);
    const month = (await periodMonthCell.textContent()) || '';
    const year = (await periodYearCell.textContent()) || '';

    return `${month.trim()} ${year.trim()}`.trim() || 'Draft';
}

export async function searchPeriodClosing(page: Page, query: string): Promise<void> {
    await searchAndWaitForApi(
        page,
        page.getByPlaceholder(/Search/i),
        query,
        '/api/period-closings',
    );
}
