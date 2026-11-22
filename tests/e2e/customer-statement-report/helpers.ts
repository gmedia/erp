import { expect, Page } from '@playwright/test';

export async function waitForCustomerStatementReportResponse(page: Page): Promise<void> {
    await page.waitForResponse(
        (response) =>
            response.url().includes('/api/reports/customer-statement') &&
            response.status() < 400,
    );
}

export async function openCustomerStatementReport(page: Page): Promise<void> {
    await page.goto('/reports/customer-statement');
    await page.waitForURL('**/reports/customer-statement', { timeout: 15000 });

    // Customer wajib dipilih dulu (W4-22/W5-14): prompt instruktif tampil
    // sebelum filter diisi dan tabel belum dirender.
    await expect(
        page.getByTestId('report-filter-prompt'),
    ).toBeVisible({ timeout: 15000 });

    await page.getByRole('button', { name: /filters/i }).click();
    const filtersDialog = page.getByRole('dialog');
    await expect(filtersDialog).toBeVisible();

    const customerTrigger = filtersDialog
        .locator('button[role="combobox"]')
        .first();
    await customerTrigger.click();
    const option = page.locator('ul.p-1 li button:visible').first();
    await expect(option).toBeVisible({ timeout: 10000 });
    await option.click();

    await Promise.all([
        waitForCustomerStatementReportResponse(page),
        filtersDialog.getByRole('button', { name: 'Apply Filters' }).click(),
    ]);

    await expect(page.locator('table')).toBeVisible({ timeout: 15000 });
}
