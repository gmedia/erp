import { expect, test } from '@playwright/test';
import { DEFAULT_E2E_EMAIL, login } from '../helpers';
import {
    VEHICLE_LOAN_DASHBOARD_PATH,
    VEHICLE_LOAN_PICKUP_PATH,
    VEHICLE_LOAN_REQUEST_PATH,
    VEHICLE_LOAN_RETURN_PATH,
    VEHICLE_LOAN_SECURITY_PATH,
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
        await expect(page.locator('#start_at')).toBeVisible();
        await expect(page.locator('#end_at')).toBeVisible();
        await expect(page.locator('#purpose')).toBeVisible();
        await expect(page.locator('#driver_id')).toBeVisible();
        await expect(page.locator('#vehicle_unit_id')).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'Simpan Draft' }),
        ).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'Kirim Permintaan' }),
        ).toBeVisible();
    });

    test('can save a draft then submit it for approval', async ({ page }) => {
        await page.goto(VEHICLE_LOAN_REQUEST_PATH);

        await page.locator('#start_at').fill('2030-01-01T08:00');
        await page.locator('#end_at').fill('2030-01-01T10:00');
        await page.locator('#purpose').fill('Draft E2E');

        const draftResponse = page.waitForResponse(
            (response) =>
                response.url().endsWith('/api/vehicle-loans') &&
                response.request().method() === 'POST' &&
                response.status() === 201,
        );
        await page.getByRole('button', { name: 'Simpan Draft' }).click();
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
        const response = await page.request.get(
            '/api/notifications/unread-count',
        );
        expect(response.ok()).toBeTruthy();
        const payload = (await response.json()) as { count?: number };
        expect(typeof payload.count).toBe('number');
    });

    test('approver can review assignment and approve from My Approvals', async ({
        page,
    }) => {
        // Seed via UI as the same approver user (admin has all permissions).
        await submitVehicleLoanRequest(page, 'E2E review assignment');

        await page.goto('/my-approvals');
        await expect(page).toHaveTitle(/My Approvals/);

        const pending = page.getByRole('tab', { name: 'Pending' });
        await expect(pending).toHaveAttribute('aria-selected', 'true');

        const approveButton = page
            .getByRole('button', { name: 'Approve' })
            .first();
        await expect(approveButton).toBeVisible({ timeout: 15000 });
        await approveButton.click();

        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();
        await expect(
            dialog.getByText('Review penugasan kendaraan'),
        ).toBeVisible();

        const review = page.waitForResponse(
            (r) => r.url().includes('/review-assignment') && r.status() < 400,
        );
        await page.getByRole('button', { name: 'Confirm Approval' }).click();
        await review;
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

        await expect(page).toHaveTitle(/Daftar Peminjaman — Security/);
        await expect(
            page.getByText('Daftar Peminjaman Kendaraan'),
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
        await expect(page.getByText('Total permintaan')).toBeVisible();
        await expect(page.getByText('Disetujui')).toBeVisible();
        await expect(page.getByText('Selesai')).toBeVisible();
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

        for (const [path, label] of [
            [VEHICLE_LOAN_REQUEST_PATH, 'request'],
            [VEHICLE_LOAN_PICKUP_PATH, 'pickup'],
            [VEHICLE_LOAN_RETURN_PATH, 'return'],
        ] as const) {
            await page.goto(path);
            const qr = page
                .locator(
                    'svg[height="96"][width="96"], svg[width="96"][height="96"]',
                )
                .first();
            await expect(qr).toBeVisible();
            await expect(
                page.getByText(new RegExp(`/vehicle-loans/${label}`)),
            ).toBeVisible();
        }
    });
});
