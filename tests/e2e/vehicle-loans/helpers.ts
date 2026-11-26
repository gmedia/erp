import { APIRequestContext, Page, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { approveApprovableAsDelegate, expectResponseOk } from '../helpers';

const enPage = (
    JSON.parse(
        readFileSync(resolve(process.cwd(), 'lang/en.json'), 'utf8'),
    ) as {
        vehicle_loan_page: Record<string, string>;
    }
).vehicle_loan_page;

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
): Promise<number> {
    await page.goto(VEHICLE_LOAN_REQUEST_PATH);

    const startDate = page.locator('#start_at_date');
    const startTime = page.locator('#start_at_time');
    const endDate = page.locator('#end_at_date');
    const endTime = page.locator('#end_at_time');
    await expect(startDate).toBeVisible();
    await expect(endDate).toBeVisible();

    const tomorrow = new Date(Date.now() + 24 * 60 * 60 * 1000);
    const dayAfter = new Date(tomorrow.getTime() + 3 * 60 * 60 * 1000);

    await startDate.fill(toDisplayDate(tomorrow));
    await startTime.fill('08:00');
    await endDate.fill(toDisplayDate(dayAfter));
    await endTime.fill('11:00');
    await page.locator('#purpose').fill(purpose);

    const responsePromise = page.waitForResponse(
        (response) =>
            response.url().includes('/api/vehicle-loans') &&
            response.request().method() === 'POST',
    );

    await page
        .getByRole('button', { name: enPage.request.submit_button })
        .click();
    const response = await responsePromise;
    await expectResponseOk(response);
    const body = (await response.json()) as {
        data?: { id?: number };
        id?: number;
    };
    const id = body.data?.id ?? body.id;
    if (!id) throw new Error('Vehicle loan POST returned no id');
    return id;
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
            start_at: new Date(
                Date.now() + 30 * 24 * 60 * 60 * 1000,
            ).toISOString(),
            end_at: new Date(
                Date.now() + 30 * 24 * 60 * 60 * 1000 + 3 * 60 * 60 * 1000,
            ).toISOString(),
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
        (item) => item.code === 'vehicle_loan_default',
    );
    if (!flow) throw new Error('vehicle_loan_default flow not found');

    // Submit the draft first so the real approval engine creates the request;
    // then approve it via My Approvals API.
    const submit = await apiRequest(
        page,
        'post',
        `/api/vehicle-loans/${id}/submit`,
    );
    expect(submit.ok()).toBeTruthy();

    await approveApprovableAsDelegate(
        page,
        id,
        'E2ESeed approved',
        'App\\Models\\VehicleLoan',
    );

    await expect
        .poll(
            async () => {
                const check = await apiRequest(
                    page,
                    'get',
                    `/api/vehicle-loans/${id}`,
                );
                if (!check.ok()) {
                    return undefined;
                }
                const detail = (await check.json()) as {
                    data?: { status?: string };
                    status?: string;
                };
                return detail.data?.status ?? detail.status;
            },
            {
                message: `Seeded loan ${id} did not reach approved status`,
                timeout: 5000,
                intervals: [250],
            },
        )
        .toBe('approved');
    return id;
}

function toDisplayDate(date: Date): string {
    const pad = (value: number) => String(value).padStart(2, '0');
    return `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()}`;
}

/** Upload a tiny valid PNG via the file input, then submit the form. */
export async function submitVehicleLoanPickup(page: Page): Promise<void> {
    await page.goto(VEHICLE_LOAN_PICKUP_PATH);

    const loadResponse = page.waitForResponse(
        (response) =>
            response.url().includes('/api/vehicle-loans?status=approved') &&
            response.request().method() === 'GET',
    );
    await page
        .getByRole('button', {
            name: new RegExp(enPage.pickup.load_button, 'i'),
        })
        .click();
    await expectResponseOk(await loadResponse);

    const loanSelect = page.locator('#loan');
    await expect(loanSelect).toBeVisible();
    await loanSelect.click();
    const firstOption = page.getByRole('option').first();
    await expect(firstOption).toBeVisible();
    await firstOption.click();
    await expect(loanSelect).toContainText(/VL-/);
    await expect(page.getByText(`${enPage.detail.purpose}:`)).toBeVisible();

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
            response.request().method() === 'POST',
    );
    await page
        .getByRole('button', { name: enPage.pickup.submit_button })
        .click();
    await expectResponseOk(await responsePromise);
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

    const loadResponse = page.waitForResponse(
        (response) =>
            response.url().includes('/api/vehicle-loans/my') &&
            response.request().method() === 'GET',
    );
    await page
        .getByRole('button', {
            name: new RegExp(enPage.return.load_button, 'i'),
        })
        .click();
    await expectResponseOk(await loadResponse);

    const pickupSelect = page.locator('#pickup');
    await expect(pickupSelect).toBeVisible();
    await pickupSelect.click();
    const firstOption = page.getByRole('option').first();
    await expect(firstOption).toBeVisible();
    await firstOption.click();
    await expect(pickupSelect).toContainText(/VL-/);

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
            response.request().method() === 'POST',
    );
    await page
        .getByRole('button', { name: enPage.return.submit_button })
        .click();
    await expectResponseOk(await responsePromise);
}
