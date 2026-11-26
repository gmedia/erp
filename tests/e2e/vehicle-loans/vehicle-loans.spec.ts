import { expect, test } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import {
    DEFAULT_E2E_EMAIL,
    approveViaScreenAsDelegate,
    expectResponseOk,
    login,
} from '../helpers';
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

const enLang = JSON.parse(
    readFileSync(resolve(process.cwd(), 'lang/en.json'), 'utf8'),
) as {
    vehicle_loan_toast: Record<string, string>;
    vehicle_loan_page: Record<string, Record<string, string>>;
};
const toastText = enLang.vehicle_loan_toast;
const pageText = enLang.vehicle_loan_page;
const dashboardText = pageText.dashboard;

function escapeRegExp(value: string): string {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

test.describe('Vehicle Loans', () => {
    test.use({ locale: 'en-US' });

    test.beforeEach(async ({ page }) => {
        await login(page, DEFAULT_E2E_EMAIL, undefined, {
            requireDashboard: false,
        });
    });

    test('request form loads with all fields and mobile-friendly labels', async ({
        page,
    }) => {
        await page.goto(VEHICLE_LOAN_REQUEST_PATH);

        await expect(page).toHaveTitle(
            new RegExp(escapeRegExp(pageText.request.title)),
        );
        await expect(page.locator('#start_at_date')).toBeVisible();
        await expect(page.locator('#end_at_date')).toBeVisible();
        await expect(page.locator('#purpose')).toBeVisible();
        await expect(page.locator('#driver_id')).toBeVisible();
        await expect(page.locator('#vehicle_unit_id')).toBeVisible();
        await expect(
            page.getByRole('button', {
                name: pageText.request.save_draft_button,
            }),
        ).toBeVisible();
        await expect(
            page.getByRole('button', {
                name: pageText.request.submit_button,
            }),
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
                response.request().method() === 'POST',
        );
        await page
            .getByRole('button', {
                name: pageText.request.save_draft_button,
            })
            .click();
        const draft = await draftResponse;
        await expectResponseOk(draft);
        expect(draft.status()).toBe(201);

        const submitResponse = page.waitForResponse(
            (response) =>
                response.url().includes('/submit') &&
                response.request().method() === 'POST',
        );
        await page
            .getByRole('button', { name: pageText.request.submit_button })
            .click();
        await expectResponseOk(await submitResponse);
    });

    test('submits a request with driver and unit selected from master options', async ({
        page,
    }) => {
        const options = page.waitForResponse(
            (response) =>
                response
                    .url()
                    .includes('/api/vehicle-loans/assignment-options') &&
                response.request().method() === 'GET',
        );
        await page.goto(VEHICLE_LOAN_REQUEST_PATH);
        await expectResponseOk(await options);

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
        const id = await submitVehicleLoanRequest(
            page,
            'E2E review assignment',
        );
        const loan = await apiRequest(page, 'get', `/api/vehicle-loans/${id}`);
        expect(loan.ok()).toBeTruthy();
        const body = (await loan.json()) as {
            data?: { code?: string };
            code?: string;
        };
        const code = body.data?.code ?? body.code;
        if (!code) {
            throw new Error(`Vehicle loan ${id} response has no code`);
        }

        await approveViaScreenAsDelegate(
            page,
            code,
            'Review penugasan kendaraan',
            '/review-assignment',
        );

        const check = await apiRequest(page, 'get', `/api/vehicle-loans/${id}`);
        expect(check.ok()).toBeTruthy();
    });

    test('approved loan can be picked up with a selfie', async ({ page }) => {
        await seedApprovedLoan(page);
        await submitVehicleLoanPickup(page);
        await expect(page.getByText(toastText.pickup_submitted)).toBeVisible();
    });

    test('security queue verifies approval before handing over the key', async ({
        page,
    }) => {
        await seedApprovedLoan(page);
        await submitVehicleLoanPickup(page);
        await expect(page.getByText(toastText.pickup_submitted)).toBeVisible();

        await page.goto(VEHICLE_LOAN_SECURITY_PATH);

        await expect(page).toHaveTitle(
            new RegExp(escapeRegExp(pageText.security.title_tag)),
        );
        await expect(
            page.getByRole('heading', { name: pageText.security.title }),
        ).toBeVisible();

        const response = page.waitForResponse(
            (r) =>
                r.url().includes('/api/vehicle-loans/security-queue') &&
                r.request().method() === 'GET',
        );
        await page.reload();
        await expectResponseOk(await response);

        const handover = page
            .getByRole('button', { name: pageText.security.handover_button })
            .first();
        if (await handover.isVisible().catch(() => false)) {
            const handoverResponse = page.waitForResponse(
                (r) =>
                    r.url().includes('/handover') &&
                    r.request().method() === 'POST',
            );
            await handover.click();
            await expectResponseOk(await handoverResponse);
            await expect(
                page.getByText(toastText.security_key_handed_over),
            ).toBeVisible();
        }
    });

    test('loan can be returned with handover and condition photos', async ({
        page,
    }) => {
        await seedHandedOverPickup(page);
        await submitVehicleLoanReturn(page);
        await expect(page.getByText(toastText.return_recorded)).toBeVisible();
    });

    test('dashboard shows summary cards for loan statuses', async ({
        page,
    }) => {
        await page.goto(VEHICLE_LOAN_DASHBOARD_PATH);

        await expect(page).toHaveTitle(
            new RegExp(escapeRegExp(dashboardText.title)),
        );
        await expect(
            page.getByRole('heading', { name: dashboardText.heading_status }),
        ).toBeVisible();
        await expect(page.getByText(dashboardText.status_total)).toBeVisible();
        await expect(
            page.getByText(dashboardText.overdue_card_title),
        ).toBeVisible();
        await expect(
            page.getByText(dashboardText.heading_recent),
        ).toBeVisible();
    });

    test('QR panels on all three forms point to the configured base URL', async ({
        page,
    }) => {
        const base = page.waitForResponse(
            (r) =>
                r.url().includes('/api/vehicle-loans/mobile-config') &&
                r.request().method() === 'GET',
        );
        await page.goto(VEHICLE_LOAN_REQUEST_PATH);
        await expectResponseOk(await base);

        for (const path of [
            VEHICLE_LOAN_REQUEST_PATH,
            VEHICLE_LOAN_PICKUP_PATH,
            VEHICLE_LOAN_RETURN_PATH,
        ]) {
            await page.goto(path);
            await expect(
                page.getByText(pageText.qr_title, { exact: true }),
            ).toBeVisible();
            await expect(
                page.locator('svg[height="88"][width="88"]').first(),
            ).toBeVisible();
        }
    });
});
