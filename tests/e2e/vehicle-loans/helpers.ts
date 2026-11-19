import { APIRequestContext, Page, expect } from '@playwright/test';

export const VEHICLE_LOAN_REQUEST_PATH = '/vehicle-loans/request';
export const VEHICLE_LOAN_PICKUP_PATH = '/vehicle-loans/pickup';
export const VEHICLE_LOAN_RETURN_PATH = '/vehicle-loans/return';
export const VEHICLE_LOAN_SECURITY_PATH = '/vehicle-loans/security';
export const VEHICLE_LOAN_DASHBOARD_PATH = '/vehicle-loans/dashboard';

/**
 * Fill and submit the vehicle-loan request form through the UI.
 * Uses datetime-local inputs so the browser sends a local datetime string;
 * the backend normalizes it to UTC in FormRequest::prepareForValidation().
 */
export async function submitVehicleLoanRequest(
    page: Page,
    purpose = 'E2E perjalanan dinas',
): Promise<void> {
    await page.goto(VEHICLE_LOAN_REQUEST_PATH);

    const start = page.locator('#start_at');
    const end = page.locator('#end_at');
    await expect(start).toBeVisible();
    await expect(end).toBeVisible();

    const tomorrow = new Date(Date.now() + 24 * 60 * 60 * 1000);
    const dayAfter = new Date(tomorrow.getTime() + 3 * 60 * 60 * 1000);

    await start.fill(toDatetimeLocal(tomorrow));
    await end.fill(toDatetimeLocal(dayAfter));
    await page.locator('#purpose').fill(purpose);

    const responsePromise = page.waitForResponse(
        (response) =>
            response.url().includes('/api/vehicle-loans') &&
            response.request().method() === 'POST' &&
            response.status() < 400,
    );

    await page.getByRole('button', { name: 'Kirim Permintaan' }).click();
    await responsePromise;
}

export async function apiRequest(
    page: Page,
    method: 'get' | 'post',
    path: string,
    options: Record<string, unknown> = {},
): Promise<Awaited<ReturnType<APIRequestContext['get']>>> {
    const token = await page.evaluate(() => localStorage.getItem('api_token'));
    if (!token) throw new Error('api_token missing from browser localStorage');

    const headers = {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
        ...(options.headers as Record<string, string> | undefined),
    };

    const request = page.context().request;
    return method === 'get'
        ? request.get(path, { ...options, headers })
        : request.post(path, { ...options, headers });
}

export async function seedApprovedLoan(page: Page): Promise<number> {
    const response = await apiRequest(page, 'post', '/api/vehicle-loans', {
        data: {
            start_at: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString(),
            end_at: new Date(Date.now() + 27 * 60 * 60 * 1000).toISOString(),
            purpose: 'E2ESeed pinjaman disetujui',
        },
    });
    expect(response.ok()).toBeTruthy();
    const payload = (await response.json()) as {
        data?: { id?: number };
        id?: number;
    };
    const id = payload.data?.id ?? payload.id;
    if (!id) throw new Error('Failed to seed approved loan: no id returned');

    const flowResponse = await apiRequest(page, 'get', '/api/approval-flows');
    expect(flowResponse.ok()).toBeTruthy();
    const flows = (await flowResponse.json()) as {
        data?: Array<{ id: number; code: string }>;
    };
    const flow = flows.data?.find(
        (item) => item.code === 'vehicle-loan.default',
    );
    if (!flow) throw new Error('vehicle-loan.default flow not found');

    // Submit the draft first so the real approval engine creates the request;
    // then approve it via My Approvals API.
    const submit = await apiRequest(
        page,
        'post',
        `/api/vehicle-loans/${id}/submit`,
    );
    expect(submit.ok()).toBeTruthy();

    const approvals = await apiRequest(page, 'get', '/api/my-approvals');
    expect(approvals.ok()).toBeTruthy();
    const approvePayload = (await approvals.json()) as {
        pending?: Array<{ request?: { id?: number; approvable_id?: number } }>;
    };
    const step = approvePayload.pending?.find(
        (item) => item.request?.approvable_id === id,
    );
    const approvalId = step?.request?.id;
    if (!approvalId)
        throw new Error('Pending approval not found for seeded loan');

    const approve = await apiRequest(
        page,
        'post',
        `/api/my-approvals/${approvalId}/approve`,
        {
            data: { comments: 'E2ESeed approved' },
        },
    );
    expect(approve.ok()).toBeTruthy();

    for (let attempt = 0; attempt < 20; attempt++) {
        const check = await apiRequest(page, 'get', `/api/vehicle-loans/${id}`);
        if (check.ok()) {
            const detail = (await check.json()) as {
                data?: { status?: string };
                status?: string;
            };
            if ((detail.data?.status ?? detail.status) === 'approved') {
                return id;
            }
        }
        await page.waitForTimeout(250);
    }

    throw new Error(`Seeded loan ${id} did not reach approved status`);
}

function toDatetimeLocal(date: Date): string {
    const pad = (value: number) => String(value).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

/** Upload a tiny valid PNG via the file input, then submit the form. */
export async function submitVehicleLoanPickup(page: Page): Promise<void> {
    await page.goto(VEHICLE_LOAN_PICKUP_PATH);

    await page
        .getByRole('button', { name: 'Muat permintaan disetujui' })
        .click();
    await page.waitForResponse(
        (response) =>
            response.url().includes('/api/vehicle-loans?status=approved') &&
            response.status() < 400,
    );

    const loanInput = page.locator('#loan');
    await expect(loanInput).toBeVisible();
    const options = page.locator('#approved-loans option');
    await expect(options.first()).toBeVisible();
    const value = await options.first().getAttribute('value');
    if (!value) throw new Error('No approved loan available for pickup');

    await loanInput.fill(value);
    await expect(page.getByText('Tujuan:')).toBeVisible();

    await page.locator('#selfie').setInputFiles({
        name: 'selfie.png',
        mimeType: 'image/png',
        buffer: Buffer.from(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            'base64',
        ),
    });

    const responsePromise = page.waitForResponse(
        (response) =>
            response.url().includes('/api/vehicle-loans/') &&
            response.url().includes('/pickups') &&
            response.status() < 400,
    );
    await page.getByRole('button', { name: 'Kirim Form Pengambilan' }).click();
    await responsePromise;
}

export async function seedHandedOverPickup(page: Page): Promise<number> {
    await seedApprovedLoan(page);

    const png = {
        name: 'selfie.png',
        mimeType: 'image/png',
        buffer: Buffer.from(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            'base64',
        ),
    };

    const loans = await apiRequest(
        page,
        'get',
        '/api/vehicle-loans?status=approved&per_page=50',
    );
    expect(loans.ok()).toBeTruthy();
    const loanPayload = (await loans.json()) as {
        data?: Array<{ id: number }>;
    };
    const loanId = loanPayload.data?.[0]?.id;
    if (!loanId) throw new Error('No approved loan available for pickup seed');

    const pickup = await apiRequest(
        page,
        'post',
        `/api/vehicle-loans/${loanId}/pickups`,
        {
            multipart: {
                photo_mode: 'with_driver',
                selfie_photo: png,
            },
        },
    );
    expect(pickup.ok()).toBeTruthy();
    const pickupPayload = (await pickup.json()) as { id?: number };
    if (!pickupPayload.id)
        throw new Error('Pickup seed failed: no id returned');

    const handover = await apiRequest(
        page,
        'post',
        `/api/vehicle-loans/pickups/${pickupPayload.id}/handover`,
    );
    expect(handover.ok()).toBeTruthy();

    return pickupPayload.id;
}

export async function submitVehicleLoanReturn(page: Page): Promise<void> {
    await page.goto(VEHICLE_LOAN_RETURN_PATH);

    await page
        .getByRole('button', { name: 'Muat data pengambilan saya' })
        .click();
    await page.waitForResponse(
        (response) =>
            response.url().includes('/api/vehicle-loans/my') &&
            response.status() < 400,
    );

    const pickupInput = page.locator('#pickup');
    await expect(pickupInput).toBeVisible();
    const options = page.locator('#my-pickups option');
    await expect(options.first()).toBeVisible();
    const value = await options.first().getAttribute('value');
    if (!value) throw new Error('No handed-over pickup available for return');

    await pickupInput.fill(value);

    const png = {
        name: 'bukti.png',
        mimeType: 'image/png',
        buffer: Buffer.from(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            'base64',
        ),
    };
    await page.locator('#handover').setInputFiles(png);
    await page
        .locator('#condition')
        .setInputFiles({ ...png, name: 'kondisi.png' });

    const responsePromise = page.waitForResponse(
        (response) =>
            response.url().includes('/returns') &&
            response.request().method() === 'POST' &&
            response.status() < 400,
    );
    await page.getByRole('button', { name: 'Kirim Pengembalian' }).click();
    await responsePromise;
}
