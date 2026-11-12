import { test, expect, resetDatabase } from './fixtures/auth.js';
import { BROOKLYN, SCHEDULE_VOLUNTEER } from './fixtures/seed.js';

async function openSchedules(page) {
  await page.getByRole('link', { name: 'Schedules' }).click();
  await page.waitForURL('**/schedule');
  await expect(page.getByRole('heading', { name: 'Schedules' })).toBeVisible();
}

async function selectServiceBody(page, optionName) {
  const schedule = page.waitForResponse((r) => r.url().includes('/api/v1/volunteers/schedule') && r.ok());
  await page.locator('#service-body-select').click();
  await page.getByRole('option', { name: optionName, exact: true }).click();
  await schedule;
}

test.describe('Schedules', () => {
  // The seeded shifts are in America/New_York, and the schedule endpoint
  // returns their times without an offset, so the browser reads them in its
  // own timezone. The week view also starts on the browser's "today". On a
  // UTC browser between 00:00 and 05:00 UTC, New York is still on the
  // previous day, so that day's shift falls before the visible week and the
  // count drops to 6. Running the browser in the shifts' timezone keeps both
  // on the same day.
  test.use({ timezoneId: 'America/New_York' });

  test.beforeAll(() => {
    resetDatabase();
  });

  test('asks for a service body before showing a calendar', async ({ authenticatedPage: page }) => {
    await openSchedules(page);
    await expect(page.getByText('Please select a service body to view the schedule')).toBeVisible();
    await expect(page.locator('.fc')).toHaveCount(0);
  });

  test('lists only service bodies with volunteer routing', async ({ authenticatedPage: page }) => {
    await openSchedules(page);
    await page.locator('#service-body-select').click();

    const options = page.getByRole('option');
    await expect(options).toHaveCount(2);
    await expect(options.nth(0)).toHaveText('-= Select a Service Body =-');
    await expect(options.nth(1)).toHaveText(BROOKLYN.option);
  });

  test('shows the volunteer shifts for the week without phone numbers', async ({ authenticatedPage: page }) => {
    await openSchedules(page);
    await selectServiceBody(page, BROOKLYN.option);

    await expect(page.locator('.fc-timeGridWeek-view')).toBeVisible();
    const shifts = page.locator('.fc-event-title', { hasText: SCHEDULE_VOLUNTEER.name });
    // Seeded with a shift on every day of the week.
    await expect(shifts).toHaveCount(7);
    await expect(shifts.first()).toHaveText(`${SCHEDULE_VOLUNTEER.name} (PHONE)`);

    // The schedule endpoint filters out volunteer phone numbers.
    await expect(page.locator('.calendar-wrapper')).not.toContainText(SCHEDULE_VOLUNTEER.phone);
  });

  test('switches between week and day views', async ({ authenticatedPage: page }) => {
    await openSchedules(page);
    await selectServiceBody(page, BROOKLYN.option);

    await page.getByRole('button', { name: 'day', exact: true }).click();
    await expect(page.locator('.fc-timeGridDay-view')).toBeVisible();
    await expect(page.locator('.fc-event-title', { hasText: SCHEDULE_VOLUNTEER.name })).toHaveCount(1);

    await page.getByRole('button', { name: 'week', exact: true }).click();
    await expect(page.locator('.fc-timeGridWeek-view')).toBeVisible();
  });
});
