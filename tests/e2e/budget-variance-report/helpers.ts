import { expect, type Page } from '@playwright/test';
import { createBudget } from '../budgets/helpers';

export async function waitForBudgetVarianceReportResponse(
    page: Page,
): Promise<void> {
    await page.waitForResponse(
        (response) =>
            response.url().includes('/api/reports/budget-variance') &&
            !response.url().includes('/export') &&
            response.status() < 400,
        { timeout: 30000 },
    );
}

async function selectFirstComboboxOption(
    page: Page,
    trigger: ReturnType<Page['locator']>,
): Promise<void> {
    await expect(trigger).toBeVisible();
    await trigger.click({ force: true });

    const option = page
        .locator(
            '[role="option"]:visible, ul[aria-busy]:visible button:visible, ul.p-1 li button:visible',
        )
        .first();
    await expect(option).toBeVisible({ timeout: 15000 });
    await option.click({ force: true });
    await expect(
        page.locator(
            '[role="option"]:visible, ul[aria-busy]:visible button:visible',
        ),
    )
        .toHaveCount(0, { timeout: 10000 })
        .catch(() => null);
}

export async function ensureBudgetExists(page: Page): Promise<string> {
    await page.goto('/budgets');
    await page.waitForURL('**/budgets', { timeout: 15000 });

    await page
        .waitForResponse(
            (r) => r.url().includes('/api/budgets') && r.status() < 400,
            { timeout: 30000 },
        )
        .catch(() => null);

    const emptyState = page.getByText(/no results|no data|no budgets/i);
    const hasRows = await page
        .locator('tbody tr')
        .first()
        .isVisible()
        .catch(() => false);

    if (!hasRows || (await emptyState.isVisible().catch(() => false))) {
        return createBudget(page);
    }

    const firstCell = page.locator('tbody tr').first().locator('td').nth(1);
    const name = (await firstCell.textContent())?.trim();
    return name && name.length > 0 ? name : createBudget(page);
}

/** budget_id is required by the API — bare navigation alone returns 422. */
export async function openBudgetVarianceReport(page: Page): Promise<void> {
    await page.goto('/reports/budget-variance');
    await page.waitForURL('**/reports/budget-variance', { timeout: 15000 });

    await expect(
        page.getByRole('button', { name: /filters/i }),
    ).toBeVisible({ timeout: 15000 });

    await page.getByRole('button', { name: /filters/i }).click();
    const filtersDialog = page.getByRole('dialog');
    await expect(filtersDialog).toBeVisible();

    const budgetTrigger = filtersDialog
        .locator('button')
        .filter({ hasText: /Select budget/i })
        .first();

    if (await budgetTrigger.isVisible().catch(() => false)) {
        await selectFirstComboboxOption(page, budgetTrigger);
    } else {
        const combobox = filtersDialog.locator('button[role="combobox"]').first();
        if (await combobox.isVisible().catch(() => false)) {
            const text = (await combobox.textContent()) ?? '';
            if (/Select budget/i.test(text) || text.trim() === '') {
                await selectFirstComboboxOption(page, combobox);
            }
        }
    }

    await Promise.all([
        waitForBudgetVarianceReportResponse(page),
        filtersDialog.getByRole('button', { name: 'Apply Filters' }).click(),
    ]);

    await expect(page.locator('table').first()).toBeVisible({ timeout: 15000 });
}

export async function applyStatusFilter(
    page: Page,
    statusLabel: RegExp | string = /Over Budget/i,
): Promise<void> {
    await page.getByRole('button', { name: /filters/i }).click();
    const filtersDialog = page.getByRole('dialog');
    await expect(filtersDialog).toBeVisible();

    const statusTrigger = filtersDialog
        .locator('button')
        .filter({ hasText: /All statuses|Within Budget|Warning|Over Budget/i })
        .first();
    await statusTrigger.click({ force: true });

    const option = page
        .locator('[role="option"]:visible')
        .filter({ hasText: statusLabel })
        .first();
    await expect(option).toBeVisible({ timeout: 10000 });
    await option.click({ force: true });

    await Promise.all([
        page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/budget-variance') &&
                !r.url().includes('/export') &&
                r.url().includes('status=') &&
                r.status() < 400,
            { timeout: 30000 },
        ),
        filtersDialog.getByRole('button', { name: 'Apply Filters' }).click(),
    ]);
}
