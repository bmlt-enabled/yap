import { test as base, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { dirname, join } from 'node:path';

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

// Locate the Laravel root (the directory holding `artisan`). Playwright is run
// from `src/` (see the Makefile and .github/actions/e2e-test), but walk up so
// the helper still works when invoked from a subdirectory.
function laravelRoot() {
  let dir = process.cwd();
  for (;;) {
    if (existsSync(join(dir, 'artisan'))) return dir;
    if (existsSync(join(dir, 'src', 'artisan'))) return join(dir, 'src');
    const parent = dirname(dir);
    if (parent === dir) {
      throw new Error(`Could not find Laravel 'artisan' at or above ${process.cwd()}`);
    }
    dir = parent;
  }
}

/**
 * Reset the test database to a known-good state.
 *
 * This shells out to artisan rather than hitting an HTTP endpoint: the old
 * POST /api/resetDatabase route was unauthenticated and unthrottled, so it was
 * removed (see issue #1575). The commands below mirror the `webServer` command
 * in playwright.config.js — `TestEnvironmentSeeder` is what creates the
 * admin/admin user the auth fixture logs in with, and it only seeds when
 * ENVIRONMENT=test.
 */
export function resetDatabase() {
  const cwd = laravelRoot();
  const options = {
    cwd,
    env: { ...process.env, ENVIRONMENT: 'test' },
    stdio: 'pipe',
  };

  const run = (args) => {
    try {
      execFileSync('php', args, options);
    } catch (error) {
      // execFileSync's default message omits the captured output, which would
      // make a CI failure here impossible to diagnose.
      const stdout = error.stdout?.toString().trim() ?? '';
      const stderr = error.stderr?.toString().trim() ?? '';
      throw new Error(
        `Database reset failed: php ${args.join(' ')} (cwd: ${cwd})\n${stderr}\n${stdout}`.trim()
      );
    }
  };

  run(['artisan', 'migrate:fresh', '--force']);
  run(['artisan', 'db:seed', '--class=TestEnvironmentSeeder', '--force']);
}

export { expect };
