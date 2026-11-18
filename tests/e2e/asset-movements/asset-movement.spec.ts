import { expect, test } from '@playwright/test';

import { generateModuleTests } from '../shared-test-factories';
import {
  createAssetMovement,
  editAssetMovement,
  pickFirstAsyncOption,
  searchAssetMovement,
} from './helpers';

generateModuleTests({
  entityName: 'Asset Movement',
  entityNamePlural: 'Asset Movements',
  route: '/asset-movements',
  apiPath: '/api/asset-movements',
  exportApiPath: '/api/asset-movements/export',
  
  createEntity: createAssetMovement,
  searchEntity: searchAssetMovement,
  editEntity: editAssetMovement,
  editUpdates: { reference: `UPDATED-MOV-${Date.now()}` },
  
  // View opens a dialog
  viewType: 'dialog',
  
  sortableColumns: [
    'Asset',
    'Type',
    'Date',
    'Ref/Notes',
    'PIC'
  ],
  
  expectedExportColumns: [
    'ID', 'Asset Code', 'Asset Name', 'Type', 'Date',
    'Origin Branch', 'Destination Branch',
    'Origin Location', 'Destination Location',
    'Origin Department', 'Destination Department',
    'Origin Employee', 'Destination Employee',
    'Reference', 'Notes', 'Recorded By', 'Created At'
  ],

  filterTests: [
      {
          filterName: 'Type',
          filterType: 'select',
          filterValue: 'transfer',
          expectedText: 'transfer'
      }
      // Asset filter (async) skipped to avoid dependency on specific seeded data
  ]
});

test('uploads a supporting document with an asset movement', async ({ page }) => {
  await page.goto('/asset-movements');
  await page.getByRole('button', { name: /Add/i }).click();

  const dialog = page.getByRole('dialog', { name: /movement/i });
  await expect(dialog).toBeVisible();

  await dialog.locator('button').filter({ hasText: /Select asset/i }).click();
  await pickFirstAsyncOption(page);

  const typeTrigger = dialog.locator('button').filter({ hasText: /Transfer|Adjustment/i }).first();
  await typeTrigger.click();
  await page.getByRole('option', { name: /Adjustment/i }).first().click();

  const reference = `DOC-MOV-${Date.now()}`;
  await dialog.locator('input[name="reference"]').fill(reference);
  await dialog.locator('input[type="file"]').setInputFiles({
    name: 'movement-proof.pdf',
    mimeType: 'application/pdf',
    buffer: Buffer.from('%PDF-1.4 test document'),
  });

  const responsePromise = page.waitForResponse(
    (response) =>
      response.url().includes('/api/asset-movements') &&
      response.request().method() === 'POST' &&
      response.status() < 400,
  );
  await dialog.getByRole('button', { name: /Record Movement/i }).last().click();
  const response = await responsePromise;
  const payload = await response.json();
  expect(payload.data.document_name).toBe('movement-proof.pdf');
  expect(payload.data.document_url).toContain('/storage/asset-movements/documents/');
});
