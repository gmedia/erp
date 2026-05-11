import { expect, Page } from '@playwright/test';

import { reloadAndWaitForApi, searchAndWaitForApi } from '../helpers';

export async function createCustomerInvoice(page: Page): Promise<string> {
    const createResult = await page.evaluate(async () => {
        const apiToken = localStorage.getItem('api_token') || '';

        const getFirstId = async (url: string): Promise<number> => {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Authorization: `Bearer ${apiToken}`,
                },
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

        const response = await fetch('/api/customer-invoices', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                Authorization: `Bearer ${apiToken}`,
            },
            body: JSON.stringify({
                customer_id: customerId,
                branch_id: branchId,
                fiscal_year_id: fiscalYearId,
                invoice_date: today,
                due_date: dueDate,
                currency: 'IDR',
                status: 'draft',
                items: [
                    {
                        account_id: accountId,
                        description: 'E2E Test Invoice Item',
                        quantity: 2,
                        unit_price: 500000,
                        discount_percent: 0,
                        tax_percent: 11,
                    },
                ],
            }),
        });

        const payload = await response.json().catch(() => ({}));
        return { ok: response.ok, invoiceNumber: payload?.data?.invoice_number || '' };
    });

    expect(createResult.ok).toBeTruthy();
    expect(createResult.invoiceNumber).not.toBe('');

    await reloadAndWaitForApi(page, '/api/customer-invoices');
    return String(createResult.invoiceNumber);
}

export async function searchCustomerInvoice(page: Page, identifier: string): Promise<void> {
    await searchAndWaitForApi(page, page.getByPlaceholder(/search/i), identifier, '/api/customer-invoices');
}

export async function editCustomerInvoice(page: Page, identifier: string, updates: Record<string, string> = {}): Promise<void> {
    const updatedNumber = updates.invoice_number || `${identifier}-EDIT`;

    const updateResult = await page.evaluate(
        async ({ findBy, nextNumber }) => {
            const apiToken = localStorage.getItem('api_token') || '';

            const findResponse = await fetch(`/api/customer-invoices?search=${encodeURIComponent(findBy)}&per_page=1`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Authorization: `Bearer ${apiToken}` },
            });
            const findPayload = await findResponse.json();
            const row = (findPayload.data || [])[0];
            if (!row?.id) return { ok: false, step: 'find' };

            const detailResponse = await fetch(`/api/customer-invoices/${row.id}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', Authorization: `Bearer ${apiToken}` },
            });
            const detail = (await detailResponse.json())?.data;
            if (!detail?.id) return { ok: false, step: 'show' };

            const updateResponse = await fetch(`/api/customer-invoices/${row.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', Authorization: `Bearer ${apiToken}` },
                body: JSON.stringify({
                    invoice_number: nextNumber,
                    customer_id: detail.customer?.id,
                    branch_id: detail.branch?.id,
                    fiscal_year_id: detail.fiscal_year?.id,
                    invoice_date: detail.invoice_date,
                    due_date: detail.due_date,
                    currency: detail.currency,
                    status: detail.status,
                    items: (detail.items || []).map((item: Record<string, unknown>) => ({
                        account_id: item.account_id,
                        description: item.description,
                        quantity: item.quantity,
                        unit_price: item.unit_price,
                        discount_percent: item.discount_percent ?? 0,
                        tax_percent: item.tax_percent ?? 0,
                    })),
                }),
            });

            return { ok: updateResponse.ok, step: 'update' };
        },
        { findBy: identifier, nextNumber: updatedNumber },
    );

    expect(updateResult).toMatchObject({ ok: true });
    await reloadAndWaitForApi(page, '/api/customer-invoices');
}
