import { expect, Page } from '@playwright/test';

import { reloadAndWaitForApi, searchAndWaitForApi } from '../helpers';

export async function createSupplierBill(page: Page): Promise<string> {
    const createResult = await page.evaluate(async () => {
        const apiToken = localStorage.getItem('api_token') || '';

        const getFirstId = async (url: string): Promise<number> => {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Authorization': `Bearer ${apiToken}`,
                },
            });
            const json = await response.json();
            const rows = json.data || json;
            return Number(rows[0].id);
        };

        const getFirstAccount = async (): Promise<number> => {
            const response = await fetch('/api/accounts?per_page=1', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Authorization': `Bearer ${apiToken}`,
                },
            });
            const json = await response.json();
            const rows = json.data || json;
            return Number(rows[0].id);
        };

        const [supplierId, branchId, fiscalYearId, accountId] = await Promise.all([
            getFirstId('/api/suppliers?per_page=1'),
            getFirstId('/api/branches?per_page=1'),
            getFirstId('/api/fiscal-years?per_page=1'),
            getFirstAccount(),
        ]);

        const today = new Date().toISOString().slice(0, 10);
        const dueDate = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10);

        const response = await fetch('/api/supplier-bills', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Authorization': `Bearer ${apiToken}`,
            },
            body: JSON.stringify({
                supplier_id: supplierId,
                branch_id: branchId,
                fiscal_year_id: fiscalYearId,
                bill_date: today,
                due_date: dueDate,
                currency: 'IDR',
                status: 'draft',
                items: [
                    {
                        account_id: accountId,
                        description: 'E2E Test Item',
                        quantity: 1,
                        unit_price: 1000000,
                        discount_percent: 0,
                        tax_percent: 11,
                    },
                ],
            }),
        });

        const payload = await response.json().catch(() => ({}));
        return {
            ok: response.ok,
            billNumber: payload?.data?.bill_number || '',
        };
    });

    expect(createResult.ok).toBeTruthy();
    expect(createResult.billNumber).not.toBe('');

    await reloadAndWaitForApi(page, '/api/supplier-bills');

    return String(createResult.billNumber);
}

export async function searchSupplierBill(page: Page, identifier: string): Promise<void> {
    await searchAndWaitForApi(
        page,
        page.getByPlaceholder(/search/i),
        identifier,
        '/api/supplier-bills',
    );
}

export async function editSupplierBill(
    page: Page,
    identifier: string,
    updates: Record<string, string> = {},
): Promise<void> {
    const updatedBillNumber = updates.bill_number || `${identifier}-EDIT`;

    const updateResult = await page.evaluate(
        async ({ findBy, nextBillNumber }) => {
            const apiToken = localStorage.getItem('api_token') || '';

            const findResponse = await fetch(
                `/api/supplier-bills?search=${encodeURIComponent(findBy)}&per_page=1`,
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Authorization': `Bearer ${apiToken}`,
                    },
                },
            );
            const findPayload = await findResponse.json();
            const row = (findPayload.data || [])[0];
            if (!row?.id) {
                return { ok: false, step: 'find', status: findResponse.status, body: findPayload };
            }

            const detailResponse = await fetch(`/api/supplier-bills/${row.id}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Authorization': `Bearer ${apiToken}`,
                },
            });
            const detailPayload = await detailResponse.json().catch(() => ({}));
            const detail = detailPayload?.data;

            if (!detail?.id) {
                return {
                    ok: false,
                    step: 'show',
                    status: detailResponse.status,
                    body: detailPayload,
                };
            }

            const payload = {
                bill_number: nextBillNumber,
                supplier_id: detail.supplier?.id,
                branch_id: detail.branch?.id,
                fiscal_year_id: detail.fiscal_year?.id,
                bill_date: detail.bill_date,
                due_date: detail.due_date,
                supplier_invoice_number: detail.supplier_invoice_number ?? null,
                payment_terms: detail.payment_terms ?? '',
                currency: detail.currency,
                status: detail.status,
                subtotal: detail.subtotal,
                tax_amount: detail.tax_amount,
                discount_amount: detail.discount_amount,
                grand_total: detail.grand_total,
                amount_paid: detail.amount_paid,
                amount_due: detail.amount_due,
                notes: detail.notes ?? '',
                items: (detail.items || []).map((item: any) => ({
                    account_id: item.account?.id,
                    description: item.description,
                    quantity: item.quantity,
                    unit_price: item.unit_price,
                    discount_percent: item.discount_percent ?? 0,
                    tax_percent: item.tax_percent ?? 0,
                })),
            };

            const updateResponse = await fetch(`/api/supplier-bills/${row.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Authorization': `Bearer ${apiToken}`,
                },
                body: JSON.stringify(payload),
            });

            const updatePayload = await updateResponse.json().catch(() => ({}));

            return {
                ok: updateResponse.ok,
                step: 'update',
                status: updateResponse.status,
                body: updatePayload,
            };
        },
        { findBy: identifier, nextBillNumber: updatedBillNumber },
    );

    expect(updateResult, JSON.stringify(updateResult)).toMatchObject({ ok: true });

    await reloadAndWaitForApi(page, '/api/supplier-bills');
}
