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

async function reloadAssetProfile(page: Page): Promise<void> {
  const [profileResponse, entityStateResponse] = await Promise.all([
    page.waitForResponse(
      response => isAssetProfileUrl(response.url()) && response.request().method() === 'GET',
      { timeout: 15000 },
    ),
    page.waitForResponse(
      response => isAssetEntityStateUrl(response.url()) && response.request().method() === 'GET',
      { timeout: 15000 },
    ),
    page.reload(),
  ]);
  await expectResponseOk(profileResponse);
  await expectResponseOk(entityStateResponse);
}

test.describe('Entity State Timeline', () => {
  test('records comment-required transition history for seeded asset', async ({ page }) => {
    await login(page, undefined, undefined, { requireDashboard: false });

    await openAssetProfile(page, 'FA-000002');

    await expect(page.getByText('Active', { exact: true }).first()).toBeVisible();

    await page.getByRole('button', { name: 'Dispose', exact: true }).first().click();

    const commentDialog = page.getByRole('dialog').filter({ hasText: 'Please provide a reason or comment for this action.' }).first();
    await expect(commentDialog).toBeVisible();
    await expect(commentDialog).toContainText('Dispose');

    const comment = 'Disposed via E2E timeline verification.';
    await commentDialog.getByPlaceholder('Enter your comment here...').fill(comment);

    const transitionResponsePromise = page.waitForResponse(
      response =>
        response.url().includes('/api/entity-states/asset/') &&
        response.url().includes('/transition') &&
        response.request().method() === 'POST',
      { timeout: 15000 },
    );

    await commentDialog.getByRole('button', { name: 'Submit' }).click();
    await expectResponseOk(await transitionResponsePromise);

    await expect(page.getByText('Transition executed successfully')).toBeVisible();
    await reloadAssetProfile(page);
    await expect(page.getByText('Disposed', { exact: true }).first()).toBeVisible();

    const timelineResponsePromise = page.waitForResponse(
      response =>
        response.url().includes('/api/entity-states/asset/') &&
        response.url().includes('/timeline') &&
        response.request().method() === 'GET',
      { timeout: 15000 },
    );
    await page.getByRole('tab', { name: 'Timeline' }).click();
    await expectResponseOk(await timelineResponsePromise);

    await expect(page.getByText('Initial pipeline assignment').first()).toBeVisible();
    await expect(page.getByText('Dispose').first()).toBeVisible();
    await expect(page.getByText(comment).first()).toBeVisible();
  });
});
