import { test as base, expect } from '@playwright/test';

async function login(page, baseURL, username, password) {
  await page.goto(`${baseURL}/admin/login`);

  // Wait for the page to load - look for any input field
  await page.waitForLoadState('networkidle');

  // Toolpad SignInPage uses MUI TextField - find by label or placeholder
  const usernameField = page.getByLabel(/username/i).or(page.getByPlaceholder(/username/i)).or(page.locator('input[type="text"]').first());
  const passwordField = page.getByLabel(/password/i).or(page.locator('input[type="password"]'));

  await usernameField.fill(username);
  await passwordField.fill(password);

  await page.getByRole('button', { name: /sign in/i }).click();
  await page.waitForURL('**/dashboard', { timeout: 15000 });

  // Add delay after login for stability
  await page.waitForTimeout(1000);
}

export const test = base.extend({
  // Use local admin user for all authenticated tests
  // BMLT users (like gnyr_admin) won't work in CI without a BMLT server
  authenticatedPage: async ({ page, baseURL }, use) => {
    await login(page, baseURL, 'admin', 'admin');
    await use(page);
  },
  adminPage: async ({ page, baseURL }, use) => {
    await login(page, baseURL, 'admin', 'admin');
    await use(page);
  },
});

export async function resetDatabase(request, baseURL) {
  await request.post(`${baseURL}/api/resetDatabase`);
}

export { expect };
