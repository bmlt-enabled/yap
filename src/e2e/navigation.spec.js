import { test, expect } from './fixtures/auth.js';

test.describe('Navigation', () => {
  test('navigate to Reports', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Reports' }).click();
    await page.waitForURL('**/reports');
    await expect(page).toHaveURL(/.*reports/);
  });

  test('navigate to Service Bodies', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Service Bodies' }).click();
    await page.waitForURL('**/serviceBodies');
    await expect(page).toHaveURL(/.*serviceBodies/);
  });

  test('navigate to Schedules', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Schedules' }).click();
    await page.waitForURL('**/schedule');
    await expect(page).toHaveURL(/.*schedule/);
  });

  test('navigate to Settings', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Settings' }).click();
    await page.waitForURL('**/settings');
    await expect(page).toHaveURL(/.*settings/);
  });

  test('navigate to Volunteers', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Volunteers' }).click();
    await page.waitForURL('**/volunteers');
    await expect(page).toHaveURL(/.*volunteers/);
  });

  test('navigate to Groups', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Groups' }).click();
    await page.waitForURL('**/groups');
    await expect(page).toHaveURL(/.*groups/);
  });

  test('navigate to Users (admin only)', async ({ adminPage: page }) => {
    await page.getByRole('link', { name: 'Users' }).click();
    await page.waitForURL('**/users');
    await expect(page).toHaveURL(/.*users/);
  });
});
