import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('user can change their name in profile settings', async ({ page }) => {
  // 1. Log in using existing credentials
  await login(page);

  // Ensure login succeeded by checking for dashboard navigation

  // 2. Navigate to the profile page
  await page.goto('/settings/profile');

  // 3. Fill the name input with a new value
  const nameInput = page.locator('input[name="name"]');
  await nameInput.fill('New Admin');

  // 4. Click the save button
  await page.click('[data-test="update-profile-button"]');

  // 5. Wait for the success indicator and assert it is visible
  const successIndicator = page.locator('text=Saved');
  await expect(successIndicator).toBeVisible();

  // 6. (Optional) Verify displayed user name reflects the new value
  // Assuming the updated name appears in an element with data-test="user-name"
  const displayedName = page.locator('[data-test="user-name"]');
  if (await displayedName.count()) {
    await expect(displayedName).toHaveText('New Admin');
  }
});