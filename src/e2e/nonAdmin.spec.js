import { test as base, expect, loginAs, resetDatabase, sessionToken } from './fixtures/auth.js';
import { BROOKLYN, BROOKLYN_ADMIN, CALLS, MANHATTAN } from './fixtures/seed.js';

// A local (database) service body admin whose only right is Brooklyn.
const test = base.extend({
  brooklynPage: async ({ browser, baseURL }, use) => {
    const { context, page } = await loginAs(browser, baseURL, BROOKLYN_ADMIN.username, BROOKLYN_ADMIN.password);
    await use(page);
    await context.close();
  },
});

test.describe('Service body admin', () => {
  test.beforeAll(() => {
    resetDatabase();
  });

  test('sees only their own service body', async ({ brooklynPage: page }) => {
    await expect(page.getByRole('heading', { name: `Welcome back, ${BROOKLYN_ADMIN.name}!` })).toBeVisible();

    await page.getByRole('link', { name: 'Service Bodies' }).click();
    await page.waitForURL('**/serviceBodies');
    const rows = page.locator('table tbody tr');
    await expect(rows).toHaveCount(1);
    await expect(rows.first()).toContainText(BROOKLYN.row);
  });

  test('cannot see other users', async ({ brooklynPage: page }) => {
    await page.getByRole('link', { name: 'Users' }).click();
    await page.waitForURL('**/users');
    const loaded = page.waitForResponse((r) => r.url().endsWith('/api/v1/users') && r.ok());
    await page.reload();
    expect(await (await loaded).json()).toEqual([]);

    await expect(page.getByRole('heading', { name: 'Users' })).toBeVisible();
    await expect(page.locator('table tbody tr')).toHaveCount(0);
    await expect(page.getByRole('columnheader', { name: 'Admin' })).toHaveCount(0);
  });

  test('reports offer only their service body, without All', async ({ brooklynPage: page }) => {
    await page.getByRole('link', { name: 'Reports' }).click();
    await page.waitForURL('**/reports');

    await page.getByRole('combobox').filter({ hasText: 'Select A Service Body' }).click();
    const options = page.getByRole('option');
    await expect(options).toHaveCount(2);
    await expect(options.nth(1)).toHaveText(BROOKLYN.option);
    await expect(page.getByRole('option', { name: 'All', exact: true })).toHaveCount(0);

    const cdr = page.waitForResponse((r) => r.url().includes('/api/v1/reports/cdr') && r.ok());
    await options.nth(1).click();
    await cdr;
    const rows = page.locator('.tabulator').first().locator('.tabulator-row');
    await expect(rows.filter({ hasText: CALLS.brooklynVolunteerCall })).toHaveCount(1);
    await expect(rows.filter({ hasText: CALLS.manhattanMeetingCall })).toHaveCount(0);
  });

  test('schedules offer only their service body', async ({ brooklynPage: page }) => {
    await page.getByRole('link', { name: 'Schedules' }).click();
    await page.waitForURL('**/schedule');
    await page.locator('#service-body-select').click();
    await expect(page.getByRole('option')).toHaveCount(2);
    await expect(page.getByRole('option').nth(1)).toHaveText(BROOKLYN.option);
  });

  test('the API refuses another service body\'s data', async ({ brooklynPage: page, request }) => {
    const headers = { Authorization: `Bearer ${await sessionToken(page)}`, Accept: 'application/json' };

    const own = await request.get(`/api/v1/voicemail?serviceBodyId=${BROOKLYN.id}`, { headers });
    expect(own.status()).toBe(200);

    const other = await request.get(`/api/v1/voicemail?serviceBodyId=${MANHATTAN.id}`, { headers });
    expect(other.status()).toBe(403);

    const serviceBodies = await (await request.get('/api/v1/rootServer/serviceBodies/user', { headers })).json();
    expect(serviceBodies.map((sb) => Number(sb.id))).toEqual([BROOKLYN.id]);
  });
});
