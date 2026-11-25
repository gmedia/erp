import { test, expect, Page } from '@playwright/test';
import { expectResponseOk, login } from '../helpers';
import { searchAsset } from '../assets/helpers';

function isAssetProfileUrl(url: string): boolean {
  return /\/api\/assets\/[^/]+\/profile$/.test(url);
}

function isAssetEntityStateUrl(url: string): boolean {
  return url.includes('/api/entity-states/asset/') && !url.includes('/timeline') && !url.includes('/approvals');
}

async function openAssetProfile(page: Page, assetName: string): Promise<void> {
  await page.goto('/assets');
  await searchAsset(page, assetName);

  const row = page.locator('tbody tr').filter({ hasText: assetName }).first();
  await expect(row).toBeVisible({ timeout: 10000 });

  await row.getByRole('button').last().click();

  const [, profileResponse, entityStateResponse] = await Promise.all([
    page.waitForURL(/\/assets\/[^/]+$/, { timeout: 15000 }),
    page.waitForResponse(
      response => isAssetProfileUrl(response.url()) && response.request().method() === 'GET',
      { timeout: 15000 },
    ),
    page.waitForResponse(
      response => isAssetEntityStateUrl(response.url()) && response.request().method() === 'GET',
      { timeout: 15000 },
    ),
    page.getByRole('menuitem', { name: 'View' }).click(),
  ]);
  await expectResponseOk(profileResponse);
  await expectResponseOk(entityStateResponse);

  await expect(page.getByText(assetName, { exact: true }).first()).toBeVisible({ timeout: 15000 });
}

test.describe('Entity State Actions', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, undefined, undefined, { requireDashboard: false });
  });

  test('can execute confirmation-required seeded asset transition from asset profile', async ({ page }) => {
    await openAssetProfile(page, 'FA-000004');

    await expect(page.getByText('Draft', { exact: true }).first()).toBeVisible();

    const cancelButton = page.getByRole('button', {
      name: 'Cancel',
      exact: true,
    }).first();
    await expect(cancelButton).toBeVisible();

    await cancelButton.click();

    const confirmDialog = page.getByRole('alertdialog', { name: 'Confirm Action' });
    await expect(confirmDialog).toBeVisible();
    await expect(confirmDialog).toContainText('Cancel');
    await expect(confirmDialog).toContainText('Cancelled');

    const transitionResponsePromise = page.waitForResponse(
      response =>
        response.url().includes('/api/entity-states/asset/') &&
        response.url().includes('/transition') &&
        response.request().method() === 'POST',
      { timeout: 15000 },
    );

    await confirmDialog.getByRole('button', { name: 'Confirm' }).click();
    await expectResponseOk(await transitionResponsePromise);

    await expect(page.getByText('Transition executed successfully')).toBeVisible();
    await expect(page.getByText('Cancelled', { exact: true }).first()).toBeVisible();
    await expect(page.getByRole('button', { name: 'Activate', exact: true })).not.toBeVisible();
  });
});
