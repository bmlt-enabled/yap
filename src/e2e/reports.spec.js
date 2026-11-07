import { readFileSync } from 'node:fs';
import { test, expect, resetDatabase } from './fixtures/auth.js';
import { BROOKLYN, CALLS, VOICEMAILS } from './fixtures/seed.js';

const BROOKLYN_FROM_NUMBERS = [CALLS.brooklynVolunteerCall, CALLS.brooklynSms, ...VOICEMAILS.map((v) => v.from)];

async function openReports(page) {
  await page.getByRole('link', { name: 'Reports' }).click();
  await page.waitForURL('**/reports');
  await expect(page.getByRole('heading', { name: 'Reports' })).toBeVisible();
}

/** Pick a service body in the Reports filter and wait for the call records. */
async function selectServiceBody(page, optionName) {
  const cdr = page.waitForResponse((r) => r.url().includes('/api/v1/reports/cdr') && r.ok());
  const metrics = page.waitForResponse((r) => r.url().includes('/api/v1/reports/metrics') && r.ok());
  await page.getByRole('combobox').filter({ hasText: 'Select A Service Body' }).click();
  await page.getByRole('option', { name: optionName, exact: true }).click();
  await Promise.all([cdr, metrics]);
  await expect(page.getByText('Loading call records...')).toHaveCount(0);
}

function cdrRows(page) {
  // The first Tabulator is the call records table; the events one is hidden.
  return page.locator('.tabulator').first().locator('.tabulator-row');
}

async function download(page, buttonName) {
  const pending = page.waitForEvent('download');
  await page.getByRole('button', { name: buttonName, exact: true }).click();
  const file = await pending;
  return { name: file.suggestedFilename(), body: readFileSync(await file.path(), 'utf8') };
}

test.describe('Reports', () => {
  test.beforeAll(() => {
    resetDatabase();
  });

  test('shows no reports until a service body is picked', async ({ authenticatedPage: page }) => {
    await openReports(page);
    await expect(page.getByRole('combobox').filter({ hasText: '-= Select A Service Body =-' })).toBeVisible();
    await expect(page.locator('#metrics-summary')).toHaveCount(0);
  });

  test('All lists every call record and the metrics summary', async ({ authenticatedPage: page }) => {
    await openReports(page);
    await selectServiceBody(page, 'All');

    await expect(cdrRows(page)).toHaveCount(6);
    for (const from of [...BROOKLYN_FROM_NUMBERS, CALLS.manhattanMeetingCall]) {
      await expect(cdrRows(page).filter({ hasText: from })).toHaveCount(1);
    }

    // One meeting search by voice (Manhattan) and one by SMS (Brooklyn).
    await expect(page.locator('#summary-meetingsearch-calls')).toHaveText('1');
    await expect(page.locator('#summary-meetingsearch-sms')).toHaveText('1');
    await expect(page.locator('#summary-volunteer-sms')).toHaveText('0');
    await expect(page.locator('#metrics .main-svg').first()).toBeVisible();
  });

  test('the service body filter limits records and metrics to that body', async ({ authenticatedPage: page }) => {
    await openReports(page);
    await selectServiceBody(page, BROOKLYN.option);

    await expect(cdrRows(page)).toHaveCount(BROOKLYN_FROM_NUMBERS.length);
    for (const from of BROOKLYN_FROM_NUMBERS) {
      await expect(cdrRows(page).filter({ hasText: from })).toHaveCount(1);
    }
    await expect(cdrRows(page).filter({ hasText: CALLS.manhattanMeetingCall })).toHaveCount(0);

    await expect(page.locator('#summary-meetingsearch-calls')).toHaveText('0');
    await expect(page.locator('#summary-meetingsearch-sms')).toHaveText('1');
  });

  test('a call record opens its events', async ({ authenticatedPage: page }) => {
    await openReports(page);
    await selectServiceBody(page, BROOKLYN.option);

    const row = cdrRows(page).filter({ hasText: CALLS.brooklynVolunteerCall });
    await expect(row).toContainText('CALL');
    await row.locator('.tabulator-cell[tabulator-field="call_events"]').click();

    const modal = page.locator('[aria-labelledby="call-events-modal"]');
    await expect(modal.getByText('Call Events')).toBeVisible();
    await expect(modal.getByText('Volunteer Search', { exact: true })).toBeVisible();
    await expect(modal.getByText('Volunteer Dialed', { exact: true })).toBeVisible();
    await expect(modal.getByText(`${BROOKLYN.name} (${BROOKLYN.id})`).first()).toBeVisible();

    await modal.getByRole('button', { name: 'Close' }).click();
    await expect(modal).toHaveCount(0);
  });

  test('the map plots geocoded volunteer lookups', async ({ authenticatedPage: page }) => {
    await openReports(page);
    await selectServiceBody(page, BROOKLYN.option);

    await expect(page.locator('.leaflet-marker-icon[title="Brooklyn, NY, USA"]')).toBeVisible();
    // Manhattan's meeting lookup is filtered out.
    await expect(page.locator('.leaflet-marker-icon[title="Manhattan, NY, USA"]')).toHaveCount(0);
    await expect(page.locator('.metrics-map-legend')).toContainText('Volunteer Lookup');
  });

  test('downloads call records as CSV and JSON', async ({ authenticatedPage: page }) => {
    await openReports(page);
    await selectServiceBody(page, BROOKLYN.option);
    await expect(cdrRows(page)).toHaveCount(BROOKLYN_FROM_NUMBERS.length);

    const csv = await download(page, 'CSV (Records)');
    expect(csv.name).toBe('yap-records.csv');
    const [header, ...lines] = csv.body.trim().split('\n');
    expect(header).toContain('"From"');
    expect(lines).toHaveLength(BROOKLYN_FROM_NUMBERS.length);
    for (const from of BROOKLYN_FROM_NUMBERS) {
      expect(csv.body).toContain(from);
    }
    expect(csv.body).not.toContain(CALLS.manhattanMeetingCall);

    const events = await download(page, 'CSV (Events)');
    expect(events.name).toBe('yap-events.csv');
    expect(events.body).toContain('Volunteer Search');
    expect(events.body).toContain('Voicemail');

    const json = await download(page, 'JSON');
    expect(json.name).toBe('yap.json');
    const records = JSON.parse(json.body);
    expect(records.map((r) => r.from_number).sort()).toEqual([...BROOKLYN_FROM_NUMBERS].sort());
  });

  test('downloads metrics JSON and map points of interest', async ({ authenticatedPage: page }) => {
    await openReports(page);
    await selectServiceBody(page, BROOKLYN.option);

    const metrics = await download(page, 'MetricsJSON');
    expect(metrics.name).toBe('metrics.json');
    const body = JSON.parse(metrics.body);
    expect(body.summary).toEqual(expect.arrayContaining([
      expect.objectContaining({ event_id: 1, counts: 1 }),
      expect.objectContaining({ event_id: 19, counts: 1 }),
    ]));

    const volunteers = await download(page, 'POI CSV (Volunteers)');
    expect(volunteers.name).toBe('poi-volunteers.csv');
    expect(volunteers.body).toBe('lat,lon,name,desc\n40.6782,-73.9442,"Brooklyn, NY, USA","1"\n');

    const meetings = await download(page, 'POI CSV (Meetings)');
    expect(meetings.name).toBe('poi-meetings.csv');
    expect(meetings.body).toBe('lat,lon,name,desc\n');
  });
});
