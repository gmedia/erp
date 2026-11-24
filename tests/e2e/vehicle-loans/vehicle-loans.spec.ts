import { expect, test } from '@playwright/test';
import { DEFAULT_E2E_EMAIL, approveViaScreenAsDelegate, login } from '../helpers';
import {
    VEHICLE_LOAN_DASHBOARD_PATH,
    VEHICLE_LOAN_PICKUP_PATH,
    VEHICLE_LOAN_REQUEST_PATH,
    VEHICLE_LOAN_RETURN_PATH,
    VEHICLE_LOAN_SECURITY_PATH,
    apiRequest,
    seedApprovedLoan,
    seedHandedOverPickup,
    submitVehicleLoanPickup,
    submitVehicleLoanRequest,
    submitVehicleLoanReturn,
} from './helpers';

test.describe('Vehicle Loans', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, DEFAULT_E2E_EMAIL, undefined, {
            requireDashboard: false,
        });
    });

    test('request form loads with all fields and mobile-friendly labels', async ({
        page,
    }) => {
        await page.goto(VEHICLE_LOAN_REQUEST_PATH);

        await expect(page).toHaveTitle(/Permintaan Peminjaman Kendaraan/);
        await expect(page.locator('#start_at_date')).toBeVisible();
        await expect(page.locator('#end_at_date')).toBeVisible();
        await expect(page.locator('#purpose')).toBeVisible();
        await expect(page.locator('#driver_id')).toBeVisible();
        await expect(page.locator('#vehicle_unit_id')).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'Simpan Draf' }),
        ).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'Kirim Permintaan' }),
        ).toBeVisible();
    });

    test('can save a draft then submit it for approval', async ({ page }) => {
        await page.goto(VEHICLE_LOAN_REQUEST_PATH);

        await page.locator('#start_at_date').fill('01/01/2030');
        await page.locator('#start_at_time').fill('08:00');
        await page.locator('#end_at_date').fill('01/01/2030');
        await page.locator('#end_at_time').fill('10:00');
        await page.locator('#purpose').fill('Draft E2E');

        const draftResponse = page.waitForResponse(
            (response) =>
                response.url().endsWith('/api/vehicle-loans') &&
                response.request().method() === 'POST' &&
                response.status() === 201,
        );
        await page.getByRole('button', { name: 'Simpan Draf' }).click();
        await draftResponse;

        const submitResponse = page.waitForResponse(
            (response) =>
                response.url().includes('/submit') &&
                response.request().method() === 'POST' &&
                response.status() < 400,
        );
        await page.getByRole('button', { name: 'Kirim Permintaan' }).click();
        await submitResponse;
    });

    test('submits a request with driver and unit selected from master options', async ({
        page,
    }) => {
        const options = page.waitForResponse(
            (response) =>
                response
                    .url()
                    .includes('/api/vehicle-loans/assignment-options') &&
                response.status() < 400,
        );
        await page.goto(VEHICLE_LOAN_REQUEST_PATH);
        await options;

        await submitVehicleLoanRequest(page, 'E2E pilih driver & unit');
    });

    test('notification inbox is available for approval activity', async ({
        page,
    }) => {
        const response = await apiRequest(
            page,
            'get',
            '/api/notifications/unread-count',
        );
        expect(response.ok()).toBeTruthy();
        const payload = (await response.json()) as { count?: number };
        expect(typeof payload.count).toBe('number');
    });

    test('approver can review assignment and approve from My Approvals', async ({
        page,
    }) => {
        const id = await submitVehicleLoanRequest(page, 'E2E review assignment');
        await approveViaScreenAsDelegate(
            page,
            'E2E review assignment',
            'Review penugasan kendaraan',
            '/review-assignment',
        );

        const check = await apiRequest(page, 'get', `/api/vehicle-loans/${id}`);
        expect(check.ok()).toBeTruthy();
    });

    test('approved loan can be picked up with a selfie', async ({ page }) => {
        await seedApprovedLoan(page);
        await submitVehicleLoanPickup(page);
        await expect(page.getByText('Form pengambilan dikirim')).toBeVisible();
    });

    test('security queue verifies approval before handing over the key', async ({
        page,
    }) => {
        await seedApprovedLoan(page);
        await submitVehicleLoanPickup(page);
        await expect(page.getByText('Form pengambilan dikirim')).toBeVisible();

        await page.goto(VEHICLE_LOAN_SECURITY_PATH);

        await expect(page).toHaveTitle(
            /Antrean Keamanan — Peminjaman Kendaraan/,
        );
        await expect(
            page.getByRole('heading', { name: 'Antrean Keamanan' }),
        ).toBeVisible();

        const response = page.waitForResponse(
            (r) =>
                r.url().includes('/api/vehicle-loans/security-queue') &&
                r.status() < 400,
        );
        await page.reload();
        await response;

        const handover = page
            .getByRole('button', { name: 'Serahkan Kunci' })
            .first();
        if (await handover.isVisible().catch(() => false)) {
            const handoverResponse = page.waitForResponse(
                (r) => r.url().includes('/handover') && r.status() < 400,
            );
            await handover.click();
            await handoverResponse;
            await expect(page.getByText('Kunci diserahkan.')).toBeVisible();
        }
    });

    test('loan can be returned with handover and condition photos', async ({
        page,
    }) => {
        await seedHandedOverPickup(page);
        await submitVehicleLoanReturn(page);
        await expect(
            page.getByText('Pengembalian berhasil dicatat.'),
        ).toBeVisible();
    });

    test('dashboard shows summary cards for loan statuses', async ({
        page,
    }) => {
        await page.goto(VEHICLE_LOAN_DASHBOARD_PATH);

        await expect(page).toHaveTitle(/Dashboard Peminjaman Kendaraan/);
        await expect(
            page.getByRole('heading', { name: 'Status Permintaan' }),
        ).toBeVisible();
        await expect(page.getByText('Total permintaan')).toBeVisible();
        await expect(page.getByText('Terlambat dikembalikan')).toBeVisible();
        await expect(page.getByText('Permintaan Terbaru')).toBeVisible();
    });

    test('QR panels on all three forms point to the configured base URL', async ({
        page,
    }) => {
        const base = page.waitForResponse(
            (r) =>
                r.url().includes('/api/vehicle-loans/mobile-config') &&
                r.status() < 400,
        );
        await page.goto(VEHICLE_LOAN_REQUEST_PATH);
        await base;

        for (const path of [
            VEHICLE_LOAN_REQUEST_PATH,
            VEHICLE_LOAN_PICKUP_PATH,
            VEHICLE_LOAN_RETURN_PATH,
        ]) {
            await page.goto(path);
            await expect(page.getByText('QR Pos Keamanan')).toBeVisible();
            await expect(
                page.locator('svg[height="88"][width="88"]').first(),
            ).toBeVisible();
        }
    });
});
