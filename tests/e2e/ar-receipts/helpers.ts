import { expect, Page } from '@playwright/test';

import { reloadAndWaitForApi, searchAndWaitForApi } from '../helpers';

export async function createArReceipt(page: Page): Promise<string> {
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
                items: [{ account_id: accountId, description: 'Receipt test item', quantity: 1, unit_price: 1000000, tax_percent: 11 }],
            }),
        });
        const invoicePayload = await invoiceResponse.json();
        const invoiceId = invoicePayload?.data?.id;

        // Send the invoice
        await fetch(`/api/customer-invoices/${invoiceId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', Authorization: `Bearer ${apiToken}` },
            body: JSON.stringify({ status: 'sent', items: [{ account_id: accountId, description: 'Receipt test item', quantity: 1, unit_price: 1000000, tax_percent: 11 }] }),
        });

        // Create receipt with allocation
        const response = await fetch('/api/ar-receipts', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', Authorization: `Bearer ${apiToken}` },
            body: JSON.stringify({
                customer_id: customerId,
                branch_id: branchId,
                fiscal_year_id: fiscalYearId,
                receipt_date: today,
                payment_method: 'bank_transfer',
                bank_account_id: accountId,
                currency: 'IDR',
                total_amount: 500000,
                status: 'draft',
                allocations: [{ customer_invoice_id: invoiceId, allocated_amount: 500000 }],
            }),
        });

        const payload = await response.json().catch(() => ({}));
        return { ok: response.ok, receiptNumber: payload?.data?.receipt_number || '' };
    });

    expect(createResult.ok).toBeTruthy();
    expect(createResult.receiptNumber).not.toBe('');

    await reloadAndWaitForApi(page, '/api/ar-receipts');
    return String(createResult.receiptNumber);
}

export async function searchArReceipt(page: Page, identifier: string): Promise<void> {
    await searchAndWaitForApi(page, page.getByPlaceholder(/search/i), identifier, '/api/ar-receipts');
}

export async function editArReceipt(page: Page, identifier: string, updates: Record<string, string> = {}): Promise<void> {
    const updatedNumber = updates.receipt_number || `${identifier}-EDIT`;

    const updateResult = await page.evaluate(
        async ({ findBy, nextNumber }) => {
            const apiToken = localStorage.getItem('api_token') || '';

            const findResponse = await fetch(`/api/ar-receipts?search=${encodeURIComponent(findBy)}&per_page=1`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Authorization: `Bearer ${apiToken}` },
            });
            const row = ((await findResponse.json()).data || [])[0];
            if (!row?.id) return { ok: false };

            const detail = (await (await fetch(`/api/ar-receipts/${row.id}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', Authorization: `Bearer ${apiToken}` },
            })).json())?.data;
            if (!detail?.id) return { ok: false };

            const updateResponse = await fetch(`/api/ar-receipts/${row.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', Authorization: `Bearer ${apiToken}` },
                body: JSON.stringify({
                    receipt_number: nextNumber,
                    customer_id: detail.customer?.id,
                    branch_id: detail.branch?.id,
                    fiscal_year_id: detail.fiscal_year?.id,
                    receipt_date: detail.receipt_date,
                    payment_method: detail.payment_method,
                    bank_account_id: detail.bank_account?.id,
                    currency: detail.currency,
                    total_amount: detail.total_amount,
                    status: detail.status,
                    allocations: (detail.allocations || []).map((a: Record<string, unknown>) => ({
                        customer_invoice_id: a.customer_invoice_id,
                        allocated_amount: a.allocated_amount,
                        discount_given: a.discount_given ?? 0,
                    })),
                }),
            });

            return { ok: updateResponse.ok };
        },
        { findBy: identifier, nextNumber: updatedNumber },
    );

    expect(updateResult).toMatchObject({ ok: true });
    await reloadAndWaitForApi(page, '/api/ar-receipts');
}
