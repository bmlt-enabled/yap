import { test, expect, loginAs, resetDatabase, sessionToken } from './fixtures/auth.js';

// A real BMLT account on the server config.test.php points at
// (latest.aws.bmlt.app). It has rights to the Greater New York Region (1) and
// its areas, and nothing in the other regions. Override with
// BMLT_E2E_USERNAME / BMLT_E2E_PASSWORD to run against another account.
const BMLT_USER = {
  username: process.env.BMLT_E2E_USERNAME || 'gnyr_admin',
  password: process.env.BMLT_E2E_PASSWORD || 'CoreysGoryStory',
};

const VISIBLE = ['Greater New York Region (1)', 'Brooklyn Area Service (1006)', 'Manhattan Area Service (1008)'];
const HIDDEN = ['Eastern New York Region (2)', 'ABCD Region (1046)', 'Western New York Region (1059)'];

test.describe('BMLT login', () => {
  test.beforeAll(() => {
    resetDatabase();
  });

  test('logs in with BMLT credentials, sees only that account\'s service bodies, and logs out', async ({ browser, baseURL, request }) => {
    const { context, page } = await loginAs(browser, baseURL, BMLT_USER.username, BMLT_USER.password);

    try {
      await expect(page.getByRole('heading', { name: /^Welcome back, / })).toBeVisible();

      // Yap keeps a BMLT-sourced, non-admin user with the rights BMLT reported.
      const token = await sessionToken(page);
      expect(token).toBeTruthy();
      const me = await (await request.get('/api/v1/user', { headers: { Authorization: `Bearer ${token}` } })).json();
      expect(me.username).toBe(BMLT_USER.username);
      expect(me.auth_source).toBe('bmlt');
      expect(Boolean(me.is_admin)).toBe(false);

      await page.getByRole('link', { name: 'Service Bodies' }).click();
      await page.waitForURL('**/serviceBodies');
      const table = page.locator('table');
      await expect(table).toBeVisible();
      for (const name of VISIBLE) {
        await expect(table.getByRole('rowheader', { name, exact: true })).toBeVisible();
      }
      for (const name of HIDDEN) {
        await expect(table.getByRole('rowheader', { name, exact: true })).toHaveCount(0);
      }

      // Not a Yap admin: the Users page lists nobody.
      await page.getByRole('link', { name: 'Users' }).click();
      await page.waitForURL('**/users');
      await expect(page.getByRole('cell', { name: 'admin', exact: true })).toHaveCount(0);

      // Sign out from the account menu.
      await page.getByRole('button', { name: 'Current User' }).click();
      const loggedOut = page.waitForResponse((r) => r.url().includes('/api/v1/logout'));
      await page.getByRole('menuitem', { name: 'Sign out' }).click();
      expect((await loggedOut).ok()).toBeTruthy();
      await page.waitForURL('**/login');
      expect(await sessionToken(page)).toBeNull();

      // The token is revoked server side.
      const after = await request.get('/api/v1/user', { headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' } });
      expect(after.status()).toBe(401);
    } finally {
      await context.close();
    }
  });

  test('rejects a wrong BMLT password', async ({ page, baseURL }) => {
    await page.goto(`${baseURL}/admin/login`);
    await page.getByLabel(/username/i).or(page.locator('input[type="text"]').first()).fill(BMLT_USER.username);
    await page.locator('input[type="password"]').fill('not-the-password');

    const attempt = page.waitForResponse((r) => r.url().includes('/api/v1/login'));
    await page.getByRole('button', { name: /sign in/i }).click();
    expect((await attempt).status()).toBe(401);
    await expect(page).toHaveURL(/.*login/);
  });
});
