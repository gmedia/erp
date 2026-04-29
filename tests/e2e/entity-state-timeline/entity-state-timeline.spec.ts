import { test, expect, Page } from '@playwright/test';
import { login } from '../helpers';
import { searchAsset } from '../assets/helpers';

async function openAssetProfile(page: Page, assetName: string): Promise<void> {
  await page.goto('/assets');
  await searchAsset(page, assetName);

  const row = page.locator('tbody tr').filter({ hasText: assetName }).first();
  await expect(row).toBeVisible({ timeout: 10000 });

  await row.getByRole('button').last().click();

  await Promise.all([
    page.waitForURL(/\/assets\/[^/]+$/, { timeout: 15000 }),
    page.waitForResponse(
      response => /\/api\/assets\/[^/]+\/profile$/.test(response.url()) && response.status() < 400,
      { timeout: 15000 },
    ),
    page.waitForResponse(
      response =>
        response.url().includes('/api/entity-states/asset/') &&
        !response.url().includes('/timeline') &&
        !response.url().includes('/approvals') &&
        response.status() < 400,
      { timeout: 15000 },
    ),
    page.getByRole('menuitem', { name: 'View' }).click(),
  ]);

  await expect(page.getByText(assetName, { exact: true }).first()).toBeVisible({ timeout: 15000 });
}

async function reloadAssetProfile(page: Page): Promise<void> {
  await Promise.all([
    page.waitForResponse(
      response => /\/api\/assets\/[^/]+\/profile$/.test(response.url()) && response.status() < 400,
      { timeout: 15000 },
    ),
    page.waitForResponse(
      response =>
        response.url().includes('/api/entity-states/asset/') &&
        !response.url().includes('/timeline') &&
        !response.url().includes('/approvals') &&
        response.status() < 400,
      { timeout: 15000 },
    ),
    page.reload(),
  ]);
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
        response.request().method() === 'POST' &&
        response.status() < 400,
      { timeout: 15000 },
    );

    await commentDialog.getByRole('button', { name: 'Submit' }).click();
    await transitionResponsePromise;

    await expect(page.getByText('Transition executed successfully')).toBeVisible();
    await reloadAssetProfile(page);
    await expect(page.getByText('Disposed', { exact: true }).first()).toBeVisible();

    await Promise.all([
      page.waitForResponse(
        response =>
          response.url().includes('/api/entity-states/asset/') &&
          response.url().includes('/timeline') &&
          response.status() < 400,
        { timeout: 15000 },
      ),
      page.getByRole('tab', { name: 'Timeline' }).click(),
    ]);

    await expect(page.getByText('Initial pipeline assignment').first()).toBeVisible();
    await expect(page.getByText('Dispose').first()).toBeVisible();
    await expect(page.getByText(comment).first()).toBeVisible();
  });
});
