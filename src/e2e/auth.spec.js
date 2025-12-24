import { test, expect } from '@playwright/test';

test.describe('Authentication', () => {
  test('login with Yap admin credentials', async ({ page, baseURL }) => {
    await page.goto(`${baseURL}/admin/login`);
    await page.waitForLoadState('networkidle');

    // Toolpad SignInPage uses MUI TextField - find by label or placeholder
    const usernameField = page.getByLabel(/username/i).or(page.getByPlaceholder(/username/i)).or(page.locator('input[type="text"]').first());
    const passwordField = page.getByLabel(/password/i).or(page.locator('input[type="password"]'));

    await usernameField.fill('admin');
    await passwordField.fill('admin');
    await page.getByRole('button', { name: /sign in/i }).click();

    await page.waitForURL('**/dashboard');
    await expect(page.locator('text=Welcome')).toBeVisible();
  });

  test('shows version on login page', async ({ page, baseURL }) => {
    await page.goto(`${baseURL}/admin/login`);
    await page.waitForLoadState('networkidle');

    await expect(page.locator('text=Version')).toBeVisible();
  });

  test('shows error for invalid credentials', async ({ page, baseURL }) => {
    await page.goto(`${baseURL}/admin/login`);
    await page.waitForLoadState('networkidle');

    const usernameField = page.getByLabel(/username/i).or(page.getByPlaceholder(/username/i)).or(page.locator('input[type="text"]').first());
    const passwordField = page.getByLabel(/password/i).or(page.locator('input[type="password"]'));

    await usernameField.fill('wronguser');
    await passwordField.fill('wrongpass');
    await page.getByRole('button', { name: /sign in/i }).click();

    // Should stay on login page or show error
    await expect(page).toHaveURL(/.*login/);
  });
});
