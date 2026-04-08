import { expect, Page } from '@playwright/test';

import { reloadAndWaitForApi, searchAndWaitForApi } from '../helpers';

export async function createPurchaseRequest(page: Page): Promise<string> {
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

        const [branchId, departmentId, requesterId, productId, unitId] =
            await Promise.all([
                getFirstId('/api/branches?per_page=1'),
                getFirstId('/api/departments?per_page=1'),
                getFirstId('/api/employees?per_page=1'),
                getFirstId('/api/products?per_page=1'),
                getFirstId('/api/units?per_page=1'),
            ]);

        const response = await fetch('/api/purchase-requests', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Authorization': `Bearer ${apiToken}`,
            },
            body: JSON.stringify({
                branch_id: branchId,
                department_id: departmentId,
                requested_by: requesterId,
                request_date: new Date().toISOString().slice(0, 10),
                priority: 'normal',
                status: 'draft',
                items: [
                    {
                        product_id: productId,
                        unit_id: unitId,
                        quantity: 2,
                        estimated_unit_price: 1000,
                    },
                ],
            }),
        });

        const payload = await response.json().catch(() => ({}));
        return {
            ok: response.ok,
            prNumber: payload?.data?.pr_number || '',
        };
    });

    expect(createResult.ok).toBeTruthy();
    expect(createResult.prNumber).not.toBe('');

    await reloadAndWaitForApi(page, '/api/purchase-requests');

    return String(createResult.prNumber);
}

export async function searchPurchaseRequest(page: Page, identifier: string): Promise<void> {
    await searchAndWaitForApi(
        page,
        page.getByPlaceholder(/search/i),
        identifier,
        '/api/purchase-requests',
    );
}

export async function editPurchaseRequest(
    page: Page,
    identifier: string,
    updates: Record<string, string> = {},
): Promise<void> {
    const updatedPrNumber = updates.pr_number || `${identifier}-EDIT`;

    const updateResult = await page.evaluate(
        async ({ findBy, nextPrNumber }) => {
            type PurchaseRequestDetailItem = {
                product?: { id?: number | string } | null;
                unit?: { id?: number | string } | null;
                quantity?: number | string;
                estimated_unit_price?: number | string;
                notes?: string | null;
            };

            const apiToken = localStorage.getItem('api_token') || '';

            const findResponse = await fetch(
                `/api/purchase-requests?search=${encodeURIComponent(findBy)}&per_page=1`,
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

            const detailResponse = await fetch(`/api/purchase-requests/${row.id}`, {
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
                pr_number: nextPrNumber,
                branch_id: detail.branch?.id,
                department_id: detail.department?.id ?? null,
                requested_by: detail.requester?.id ?? null,
                request_date: detail.request_date,
                required_date: detail.required_date ?? null,
                priority: detail.priority,
                status: detail.status,
                notes: detail.notes ?? '',
                approved_by: detail.approved_by?.id ?? null,
                approved_at: detail.approved_at ?? null,
                rejection_reason: detail.rejection_reason ?? '',
                items: (detail.items as PurchaseRequestDetailItem[] || []).map((item) => ({
                    product_id: item.product?.id,
                    unit_id: item.unit?.id,
                    quantity: item.quantity,
                    estimated_unit_price: item.estimated_unit_price ?? 0,
                    notes: item.notes ?? '',
                })),
            };

            const updateResponse = await fetch(`/api/purchase-requests/${row.id}`, {
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
        { findBy: identifier, nextPrNumber: updatedPrNumber },
    );

    expect(updateResult, JSON.stringify(updateResult)).toMatchObject({ ok: true });

    await reloadAndWaitForApi(page, '/api/purchase-requests');
}
