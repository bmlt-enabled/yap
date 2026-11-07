import { test, expect, resetDatabase } from './fixtures/auth.js';

// The e2e server runs with TWILIO_FAKE_MODE=true and the seeder primes the
// Google geocode/time zone answers, so the upgrade advisor runs all the way to
// "Ready To Yap!" and the Twilio compliance checks. The signature-validation
// bypass from .env.pipeline is a `warn` check, which makes the overall state
// "Warning".
const TWILIO_COMPLIANCE_CHECKS = [
  'Twilio account type',
  'US voice geo permissions',
  'Trust Hub profile',
  'SMS registration',
  'Toll-free verification',
];

function checkItem(page, label) {
  return page.getByRole('listitem').filter({ hasText: label });
}

test.describe('Dashboard and System Health', () => {
  test.beforeAll(() => {
    resetDatabase();
  });

  test('dashboard greets the user and shows version and system status', async ({ authenticatedPage: page, request }) => {
    const { version } = await (await request.get('/api/v1/version')).json();

    await expect(page.getByRole('heading', { name: 'Welcome back, admin!' })).toBeVisible();
    await expect(page.getByRole('heading', { name: `v${version}` })).toBeVisible();

    // System Status card reflects the upgrade advisor's answer.
    await expect(page.getByText('Ready To Yap!')).toBeVisible({ timeout: 20000 });
    await expect(page.getByText('Issues Detected')).toBeVisible();
    await expect(page.locator('.MuiChip-root', { hasText: /^Warning$/ })).toBeVisible();

    await expect(page.getByRole('link', { name: 'View Documentation' })).toHaveAttribute('href', 'https://yap.bmlt.app');
    await expect(page.getByRole('link', { name: 'API Reference' })).toHaveAttribute('href', '/api/v1/documentation');
  });

  test('dashboard links to System Health', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'View System Health' }).click();
    await page.waitForURL('**/systemHealth');
    await expect(page.getByRole('heading', { name: 'System Health' })).toBeVisible();
  });

  test('system health lists upgrade advisor and Twilio compliance checks', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'System Health', exact: true }).click();
    await page.waitForURL('**/systemHealth');

    await expect(page.getByRole('alert').filter({ hasText: 'Ready To Yap!' })).toBeVisible({ timeout: 20000 });
    await expect(page.locator('.MuiChip-root', { hasText: /^Warning$/ })).toBeVisible();

    // Preflight checks, with their status chips.
    await expect(checkItem(page, 'Required settings')).toContainText('pass');
    await expect(checkItem(page, 'Twilio auth token')).toContainText('pass');
    await expect(checkItem(page, 'Twilio signature bypass')).toContainText('warn');
    await expect(checkItem(page, 'MySQL version')).toContainText('pass');
    await expect(checkItem(page, 'Users table schema')).toContainText('pass');

    // Twilio compliance checks are appended once the Twilio credentials work.
    // The fake Twilio API has no account/Trust Hub/messaging resources, so
    // these come back `skip` with an explanation.
    for (const label of TWILIO_COMPLIANCE_CHECKS) {
      await expect(checkItem(page, label)).toBeVisible();
    }
    await expect(checkItem(page, 'Twilio account type')).toContainText('Unable to verify account type');
    await expect(checkItem(page, 'Toll-free verification')).toContainText('No toll-free phone numbers are configured.');
  });

  test('system health refresh re-runs the upgrade advisor', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'System Health', exact: true }).click();
    await page.waitForURL('**/systemHealth');
    await expect(checkItem(page, 'Required settings')).toBeVisible({ timeout: 20000 });

    const rerun = page.waitForResponse((r) => r.url().includes('/api/v1/upgrade') && r.ok());
    await page.getByRole('button', { name: 'Refresh' }).click();
    await rerun;
    await expect(checkItem(page, 'Required settings')).toContainText('pass');
  });

  test('upgrade advisor JSON is available without logging in', async ({ request }) => {
    const response = await request.get('/api/v1/upgrade');
    expect(response.ok()).toBeTruthy();
    const body = await response.json();
    expect(body.status).toBe(true);
    expect(body.message).toBe('Ready To Yap!');
    expect(body.checks.map((c) => c.label)).toEqual(expect.arrayContaining(TWILIO_COMPLIANCE_CHECKS));
  });
});
