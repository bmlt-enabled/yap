import { test, expect } from '@playwright/test';

test.describe('Authentication', () => {
  test.beforeAll(async ({ request, baseURL }) => {
    await request.post(`${baseURL}/api/resetDatabase`);
  });

  test('login with BMLT credentials', async ({ page, baseURL }) => {
    await page.goto(`${baseURL}/admin/login`);

    await page.locator('input[name="email"]').fill('gnyr_admin');
    await page.locator('input[name="password"]').fill('CoreysGoryStory');
    await page.getByRole('button', { name: /sign in/i }).click();

    await page.waitForURL('**/dashboard');
    await expect(page.locator('text=Welcome')).toBeVisible();
  });

  test('login with Yap credentials', async ({ page, baseURL }) => {
    await page.goto(`${baseURL}/admin/login`);

    await page.locator('input[name="email"]').fill('admin');
    await page.locator('input[name="password"]').fill('admin');
    await page.getByRole('button', { name: /sign in/i }).click();

    await page.waitForURL('**/dashboard');
    await expect(page.locator('text=Welcome')).toBeVisible();
  });

  test('shows version on login page', async ({ page, baseURL }) => {
    await page.goto(`${baseURL}/admin/login`);

    await expect(page.locator('text=Version')).toBeVisible();
  });
});
