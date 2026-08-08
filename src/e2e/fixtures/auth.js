import { test as base, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { dirname, join } from 'node:path';
import {
  bootstrapAuthStorage,
  getAuthStorage,
  invalidateCachedAuth,
} from '../auth-storage.js';

async function openAuthenticatedPage(context, baseURL) {
  const page = await context.newPage();
  await page.goto(`${baseURL}/admin/dashboard`);
  // Cached storageState restores localStorage but not the current URL — land on
  // the dashboard so sidebar navigation links exist before each test runs.
  await page.getByRole('link', { name: 'Reports' }).waitFor({ state: 'visible', timeout: 30000 });
  return page;
}

async function createAuthenticatedContext(browser, baseURL) {
  const storage = await getAuthStorage(browser, baseURL);
  return browser.newContext({ storageState: storage });
}

export const test = base.extend({
  authenticatedPage: async ({ browser, baseURL }, use) => {
    let context = await createAuthenticatedContext(browser, baseURL);
    let page = await openAuthenticatedPage(context, baseURL);

    if (page.url().includes('/login')) {
      await context.close();
      invalidateCachedAuth();
      await bootstrapAuthStorage(browser, baseURL);
      context = await createAuthenticatedContext(browser, baseURL);
      page = await openAuthenticatedPage(context, baseURL);
    }

    await use(page);
    await context.close();
  },
  adminPage: async ({ browser, baseURL }, use) => {
    let context = await createAuthenticatedContext(browser, baseURL);
    let page = await openAuthenticatedPage(context, baseURL);

    if (page.url().includes('/login')) {
      await context.close();
      invalidateCachedAuth();
      await bootstrapAuthStorage(browser, baseURL);
      context = await createAuthenticatedContext(browser, baseURL);
      page = await openAuthenticatedPage(context, baseURL);
    }

    await use(page);
    await context.close();
  },
});

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

export function resetDatabase() {
  const cwd = laravelRoot();
  const options = {
    cwd,
    env: { ...process.env, ENVIRONMENT: 'test', E2E_TESTING: '1' },
    stdio: 'pipe',
  };

  const run = (args) => {
    try {
      execFileSync('php', args, options);
    } catch (error) {
      const stdout = error.stdout?.toString().trim() ?? '';
      const stderr = error.stderr?.toString().trim() ?? '';
      throw new Error(
        `Database reset failed: php ${args.join(' ')} (cwd: ${cwd})\n${stderr}\n${stdout}`.trim()
      );
    }
  };

  run(['artisan', 'migrate:fresh', '--force']);
  run(['artisan', 'db:seed', '--class=TestEnvironmentSeeder', '--force']);
  invalidateCachedAuth();
}

export { expect, invalidateCachedAuth };
