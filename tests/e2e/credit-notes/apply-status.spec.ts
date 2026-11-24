import { expect, Page, test } from '@playwright/test';

import { login, reloadAndWaitForApi, searchAndWaitForApi } from '../helpers';
import { createCreditNote } from './helpers';

type ApiBody = { data: { id: number; status?: string; journal_entry_id?: number | null; grand_total?: string; amount_due?: string; customer_invoice?: { id: number } } };

async function fetchJson(page: Page, url: string): Promise<ApiBody> {
    return page.evaluate(async (target: string): Promise<ApiBody> => {
        const apiToken = localStorage.getItem('api_token') || '';
        const response = await fetch(target, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', Authorization: `Bearer ${apiToken}` },
        });
        return response.json();
    }, url);
}

test.describe('Credit Note apply via UI', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        const listResponse = page.waitForResponse(
            (r) => r.url().includes('/api/credit-notes') && r.status() < 400,
        );
        await page.goto('/credit-notes');
        await listResponse;
    });

    test('setting status Applied in the edit form posts journal and reduces invoice amount_due', async ({ page }) => {
        const creditNoteNumber = await createCreditNote(page);
        await searchAndWaitForApi(page, page.getByPlaceholder(/search/i), creditNoteNumber, '/api/credit-notes');

        const row = page.locator('tbody tr').filter({ hasText: creditNoteNumber }).first();
        await expect(row).toBeVisible();
        await row.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();

        const creditNoteList = await page.evaluate(async (search: string): Promise<{ data: { id: number }[] }> => {
            const apiToken = localStorage.getItem('api_token') || '';
            const response = await fetch(`/api/credit-notes?search=${encodeURIComponent(search)}&per_page=1`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', Authorization: `Bearer ${apiToken}` },
            });
            return response.json();
        }, creditNoteNumber);
        const creditNoteId = creditNoteList.data[0].id;
        const before = await fetchJson(page, `/api/credit-notes/${creditNoteId}`);
        const invoiceId = before.data.customer_invoice.id;
        const invoiceBefore = await fetchJson(page, `/api/customer-invoices/${invoiceId}`);
        const amountDueBefore = Number(invoiceBefore.data.amount_due);
        const grandTotal = Number(before.data.grand_total);

        await dialog.getByRole('combobox', { name: 'Status' }).click();
        await page.getByRole('option', { name: 'Applied' }).click();

        const saveResponse = page.waitForResponse(
            (r) => r.url().includes(`/api/credit-notes/${creditNoteId}`) && r.request().method() === 'PUT' && r.status() < 400,
        );
        await dialog.getByRole('button', { name: 'Update', exact: true }).click();
        await saveResponse;

        const after = await fetchJson(page, `/api/credit-notes/${creditNoteId}`);
        expect(after.data.status).toBe('applied');
        expect(after.data.journal_entry_id).not.toBeNull();

        const invoiceAfter = await fetchJson(page, `/api/customer-invoices/${invoiceId}`);
        expect(Number(invoiceAfter.data.amount_due)).toBeCloseTo(amountDueBefore - grandTotal, 2);

        await reloadAndWaitForApi(page, '/api/credit-notes');
    });
});
