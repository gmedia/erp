import { expect, Page } from '@playwright/test';

import { reloadAndWaitForApi, searchAndWaitForApi } from '../helpers';

export async function createApPayment(page: Page): Promise<string> {
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

        const [supplierId, branchId, fiscalYearId, bankAccountId] = await Promise.all([
            getFirstId('/api/suppliers?per_page=1'),
            getFirstId('/api/branches?per_page=1'),
            getFirstId('/api/fiscal-years?per_page=1'),
            getFirstAccount(),
        ]);

        const billResponse = await fetch('/api/supplier-bills', {
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
                bill_date: new Date().toISOString().slice(0, 10),
                due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
                currency: 'IDR',
                status: 'confirmed',
                items: [
                    {
                        account_id: await getFirstAccount(),
                        description: 'E2E Test Bill for Payment',
                        quantity: 1,
                        unit_price: 1000000,
                        discount_percent: 0,
                        tax_percent: 11,
                    },
                ],
            }),
        });

        const billPayload = await billResponse.json().catch(() => ({}));
        const billId = billPayload?.data?.id;

        if (!billId) {
            throw new Error('Failed to create supplier bill for payment allocation');
        }

        const response = await fetch('/api/ap-payments', {
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
                payment_date: new Date().toISOString().slice(0, 10),
                payment_method: 'bank_transfer',
                bank_account_id: bankAccountId,
                currency: 'IDR',
                total_amount: 1000000,
                status: 'draft',
                allocations: [
                    {
                        supplier_bill_id: billId,
                        allocated_amount: 1000000,
                    },
                ],
            }),
        });

        const payload = await response.json().catch(() => ({}));
        return {
            ok: response.ok,
            paymentNumber: payload?.data?.payment_number || '',
        };
    });

    expect(createResult.ok).toBeTruthy();
    expect(createResult.paymentNumber).not.toBe('');

    await reloadAndWaitForApi(page, '/api/ap-payments');

    return String(createResult.paymentNumber);
}

export async function searchApPayment(page: Page, identifier: string): Promise<void> {
    await searchAndWaitForApi(
        page,
        page.getByPlaceholder(/search/i),
        identifier,
        '/api/ap-payments',
    );
}

export async function editApPayment(
    page: Page,
    identifier: string,
    updates: Record<string, string> = {},
): Promise<void> {
    const updatedPaymentNumber = updates.payment_number || `${identifier}-EDIT`;

    const updateResult = await page.evaluate(
        async ({ findBy, nextPaymentNumber }) => {
            const apiToken = localStorage.getItem('api_token') || '';

            const findResponse = await fetch(
                `/api/ap-payments?search=${encodeURIComponent(findBy)}&per_page=1`,
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

            const detailResponse = await fetch(`/api/ap-payments/${row.id}`, {
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
                payment_number: nextPaymentNumber,
                supplier_id: detail.supplier?.id,
                branch_id: detail.branch?.id,
                fiscal_year_id: detail.fiscal_year?.id,
                payment_date: detail.payment_date,
                payment_method: detail.payment_method,
                bank_account_id: detail.bank_account?.id,
                currency: detail.currency,
                total_amount: detail.total_amount,
                total_allocated: detail.total_allocated,
                total_unallocated: detail.total_unallocated,
                reference: detail.reference ?? null,
                status: detail.status,
                notes: detail.notes ?? '',
                allocations: (detail.allocations || []).map((allocation: Record<string, unknown>) => ({
                    supplier_bill_id: allocation.supplier_bill_id,
                    allocated_amount: allocation.allocated_amount,
                })),
            };

            const updateResponse = await fetch(`/api/ap-payments/${row.id}`, {
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
        { findBy: identifier, nextPaymentNumber: updatedPaymentNumber },
    );

    expect(updateResult, JSON.stringify(updateResult)).toMatchObject({ ok: true });

    await reloadAndWaitForApi(page, '/api/ap-payments');
}
