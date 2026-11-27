import { expect, test } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { DEFAULT_E2E_EMAIL, expectResponseOk, login } from '../helpers';
import {
    VEHICLE_LOAN_SECURITY_PATH,
    apiRequest,
    seedApprovedLoan,
} from './helpers';

const enLang = JSON.parse(
    readFileSync(resolve(process.cwd(), 'lang/en.json'), 'utf8'),
) as {
    vehicle_loan_page: { status: Record<string, string> };
};
const awaitingLabel = enLang.vehicle_loan_page.status.awaiting_key_handover;

const PNG = {
    name: 'selfie.png',
    mimeType: 'image/png',
    buffer: Buffer.from(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
        'base64',
    ),
};

test.describe('Vehicle Loans awaiting key handover', () => {
    test.use({ locale: 'en-US' });

    test.beforeEach(async ({ page }) => {
        await login(page, DEFAULT_E2E_EMAIL, undefined, {
            requireDashboard: false,
        });
    });

    test('pickup without key handover shows Awaiting Key Handover in the security queue', async ({
        page,
    }) => {
        const loanId = await seedApprovedLoan(page);

        const pickup = await apiRequest(
            page,
            'post',
            `/api/vehicle-loans/${loanId}/pickups`,
            {
                multipart: {
                    photo_mode: 'with_driver',
                    selfie_photo: PNG,
                },
            },
        );
        expect(pickup.ok()).toBeTruthy();

        const queue = page.waitForResponse(
            (response) =>
                response.url().includes('/api/vehicle-loans/security-queue') &&
                response.request().method() === 'GET',
        );
        await page.goto(VEHICLE_LOAN_SECURITY_PATH);
        await expectResponseOk(await queue);

        await expect(
            page.getByText(awaitingLabel, { exact: true }).first(),
        ).toBeVisible();
    });
});
