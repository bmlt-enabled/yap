import { test as base, expect } from '@playwright/test';

export const test = base.extend({
  authenticatedPage: async ({ page, baseURL }, use) => {
    await page.goto(`${baseURL}/admin/login`);
    await page.locator('input[name="email"]').fill('gnyr_admin');
    await page.locator('input[name="password"]').fill('CoreysGoryStory');
    await page.getByRole('button', { name: /sign in/i }).click();
    await page.waitForURL('**/dashboard');
    await use(page);
  },
  adminPage: async ({ page, baseURL }, use) => {
    await page.goto(`${baseURL}/admin/login`);
    await page.locator('input[name="email"]').fill('admin');
    await page.locator('input[name="password"]').fill('admin');
    await page.getByRole('button', { name: /sign in/i }).click();
    await page.waitForURL('**/dashboard');
    await use(page);
  },
});

export async function resetDatabase(request, baseURL) {
  await request.post(`${baseURL}/api/resetDatabase`);
}

export { expect };
