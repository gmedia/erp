import { expect, Page } from '@playwright/test';

import { reloadAndWaitForApi, searchAndWaitForApi } from '../helpers';

export async function createCreditNote(page: Page): Promise<string> {
    const createResult = await page.evaluate(async () => {
        const apiToken = localStorage.getItem('api_token') || '';

        const getFirstId = async (url: string): Promise<number> => {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Authorization: `Bearer ${apiToken}` },
            });
            const json = await response.json();
            const rows = json.data || json;
            return Number(rows[0].id);
        };

        const [customerId, branchId, fiscalYearId, accountId] = await Promise.all([
            getFirstId('/api/customers?per_page=1'),
            getFirstId('/api/branches?per_page=1'),
            getFirstId('/api/fiscal-years?per_page=1'),
            getFirstId('/api/accounts?per_page=1'),
        ]);

        const today = new Date().toISOString().slice(0, 10);
        const dueDate = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10);

        // Create a sent invoice first
        const invoiceResponse = await fetch('/api/customer-invoices', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', Authorization: `Bearer ${apiToken}` },
            body: JSON.stringify({
                customer_id: customerId,
                branch_id: branchId,
                fiscal_year_id: fiscalYearId,
                invoice_date: today,
                due_date: dueDate,
                currency: 'IDR',
                status: 'draft',
                items: [{ account_id: accountId, description: 'CN test item', quantity: 1, unit_price: 2000000, tax_percent: 11 }],
            }),
        });
        const invoiceId = (await invoiceResponse.json())?.data?.id;

        // Send the invoice
        await fetch(`/api/customer-invoices/${invoiceId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', Authorization: `Bearer ${apiToken}` },
            body: JSON.stringify({ status: 'sent', items: [{ account_id: accountId, description: 'CN test item', quantity: 1, unit_price: 2000000, tax_percent: 11 }] }),
        });

        // Create credit note
        const response = await fetch('/api/credit-notes', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', Authorization: `Bearer ${apiToken}` },
            body: JSON.stringify({
                customer_id: customerId,
                customer_invoice_id: invoiceId,
                branch_id: branchId,
                fiscal_year_id: fiscalYearId,
                credit_note_date: today,
                reason: 'return',
                status: 'draft',
                items: [{ account_id: accountId, description: 'E2E CN Item', quantity: 1, unit_price: 100000, tax_percent: 11 }],
            }),
        });

        const payload = await response.json().catch(() => ({}));
        return { ok: response.ok, creditNoteNumber: payload?.data?.credit_note_number || '' };
    });

    expect(createResult.ok).toBeTruthy();
    expect(createResult.creditNoteNumber).not.toBe('');

    await reloadAndWaitForApi(page, '/api/credit-notes');
    return String(createResult.creditNoteNumber);
}

export async function searchCreditNote(page: Page, identifier: string): Promise<void> {
    await searchAndWaitForApi(page, page.getByPlaceholder(/search/i), identifier, '/api/credit-notes');
}

export async function editCreditNote(page: Page, identifier: string, updates: Record<string, string> = {}): Promise<void> {
    const updatedNumber = updates.credit_note_number || `${identifier}-EDIT`;

    const updateResult = await page.evaluate(
        async ({ findBy, nextNumber }) => {
            const apiToken = localStorage.getItem('api_token') || '';

            const findResponse = await fetch(`/api/credit-notes?search=${encodeURIComponent(findBy)}&per_page=1`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Authorization: `Bearer ${apiToken}` },
            });
            const row = ((await findResponse.json()).data || [])[0];
            if (!row?.id) return { ok: false };

            const detail = (await (await fetch(`/api/credit-notes/${row.id}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', Authorization: `Bearer ${apiToken}` },
            })).json())?.data;
            if (!detail?.id) return { ok: false };

            const updateResponse = await fetch(`/api/credit-notes/${row.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', Authorization: `Bearer ${apiToken}` },
                body: JSON.stringify({
                    credit_note_number: nextNumber,
                    customer_id: detail.customer?.id,
                    customer_invoice_id: detail.customer_invoice?.id ?? null,
                    branch_id: detail.branch?.id,
                    fiscal_year_id: detail.fiscal_year?.id,
                    credit_note_date: detail.credit_note_date,
                    reason: detail.reason,
                    status: detail.status,
                    items: (detail.items || []).map((item: Record<string, unknown>) => ({
                        account_id: item.account_id,
                        description: item.description,
                        quantity: item.quantity,
                        unit_price: item.unit_price,
                        tax_percent: item.tax_percent ?? 0,
                    })),
                }),
            });

            return { ok: updateResponse.ok };
        },
        { findBy: identifier, nextNumber: updatedNumber },
    );

    expect(updateResult).toMatchObject({ ok: true });
    await reloadAndWaitForApi(page, '/api/credit-notes');
}
