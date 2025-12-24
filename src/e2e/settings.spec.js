import { test, expect } from './fixtures/auth.js';

test.describe('Settings', () => {
  test('can view settings page', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Settings' }).click();
    await page.waitForURL('**/settings');

    await expect(page).toHaveURL(/.*settings/);
  });

  test('displays BMLT server URL', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Settings' }).click();
    await page.waitForURL('**/settings');

    // Settings should show BMLT configuration - use first() since exact match includes emoji
    await expect(page.getByRole('cell', { name: /^bmlt_root_server/ }).first()).toBeVisible({ timeout: 10000 });
  });

  test('can view configuration options', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Settings' }).click();
    await page.waitForURL('**/settings');

    // There should be a table or list of settings
    await expect(page.locator('table, [role="grid"]')).toBeVisible();
  });
});
