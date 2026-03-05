import { expect, Page } from '@playwright/test';

export async function createPurchaseRequest(page: Page): Promise<string> {
    const createResult = await page.evaluate(async () => {
        const csrf = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') || '';
        const xsrfCookie = document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='));
        const xsrfToken = xsrfCookie
            ? decodeURIComponent(xsrfCookie.split('=')[1])
            : '';

        const getFirstId = async (url: string): Promise<number> => {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
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
                'X-CSRF-TOKEN': csrf,
                'X-XSRF-TOKEN': xsrfToken,
                'X-Requested-With': 'XMLHttpRequest',
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

    await page.reload();
    await page
        .waitForResponse((r) => r.url().includes('/api/purchase-requests') && r.status() < 400)
        .catch(() => null);

    return String(createResult.prNumber);
}

export async function searchPurchaseRequest(page: Page, identifier: string): Promise<void> {
    await page.getByPlaceholder(/search/i).fill(identifier);
    await page.keyboard.press('Enter');
    await page
        .waitForResponse(
            (r) => r.url().includes('/api/purchase-requests') && r.status() < 400,
        )
        .catch(() => null);
}

export async function editPurchaseRequest(
    page: Page,
    identifier: string,
    updates: Record<string, string> = {},
): Promise<void> {
    const updatedPrNumber = updates.pr_number || `${identifier}-EDIT`;

    const updateResult = await page.evaluate(
        async ({ findBy, nextPrNumber }) => {
            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') || '';
            const xsrfCookie = document.cookie
                .split('; ')
                .find((row) => row.startsWith('XSRF-TOKEN='));
            const xsrfToken = xsrfCookie
                ? decodeURIComponent(xsrfCookie.split('=')[1])
                : '';

            const findResponse = await fetch(
                `/api/purchase-requests?search=${encodeURIComponent(findBy)}&per_page=1`,
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            );
            const findPayload = await findResponse.json();
            const row = (findPayload.data || [])[0];
            if (!row?.id) {
                return { ok: false };
            }

            const updateResponse = await fetch(`/api/purchase-requests/${row.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-XSRF-TOKEN': xsrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ pr_number: nextPrNumber }),
            });

            return { ok: updateResponse.ok };
        },
        { findBy: identifier, nextPrNumber: updatedPrNumber },
    );

    expect(updateResult.ok).toBeTruthy();

    await page.reload();
    await page
        .waitForResponse(
            (r) => r.url().includes('/api/purchase-requests') && r.status() < 400,
        )
        .catch(() => null);
}
