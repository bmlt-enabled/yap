import { test, expect, resetDatabase } from './fixtures/auth.js';
import { BROOKLYN, VOICEMAILS } from './fixtures/seed.js';

// The e2e server runs with TWILIO_FAKE_MODE=true: recording deletes and /media
// fetches are recorded in the database instead of going to Twilio, and
// /fake-twilio/requests lists them.

async function openVoicemail(page) {
  await page.getByRole('link', { name: 'Service Bodies' }).click();
  await page.waitForURL('**/serviceBodies');

  const loaded = page.waitForResponse((r) => r.url().includes('/api/v1/voicemail?') && r.ok());
  await page.getByRole('row').filter({ hasText: BROOKLYN.row }).getByRole('button', { name: 'Voicemail' }).click();
  await loaded;

  const dialog = page.getByRole('dialog');
  await expect(dialog.getByRole('heading', { name: `Voicemail for ${BROOKLYN.name}` })).toBeVisible();
  return dialog;
}

function voicemailRow(dialog, voicemail) {
  return dialog.getByRole('row').filter({ hasText: voicemail.callsid });
}

async function fakeTwilioRequests(request, resource) {
  const response = await request.get(`/fake-twilio/requests?resource=${resource}`);
  expect(response.ok()).toBeTruthy();
  return (await response.json()).requests;
}

test.describe('Voicemail', () => {
  test.beforeAll(() => {
    resetDatabase();
  });

  test('lists the service body voicemails', async ({ authenticatedPage: page }) => {
    const dialog = await openVoicemail(page);

    for (const voicemail of VOICEMAILS) {
      const row = voicemailRow(dialog, voicemail);
      await expect(row).toContainText(voicemail.from);
      await expect(row).toContainText(voicemail.pin);
      await expect(row.getByRole('button', { name: 'Play' })).toBeVisible();
    }
    await expect(dialog.getByRole('button', { name: 'Delete selected' })).toBeDisabled();
  });

  test('Play opens the recording through the signed /media proxy', async ({ authenticatedPage: page, request }) => {
    const [voicemail] = VOICEMAILS;
    const dialog = await openVoicemail(page);

    const popupPromise = page.waitForEvent('popup');
    await voicemailRow(dialog, voicemail).getByRole('button', { name: 'Play' }).click();
    const popup = await popupPromise;
    const mediaUrl = new URL(popup.url());
    await popup.close();

    expect(mediaUrl.pathname).toBe('/media');
    expect(mediaUrl.searchParams.get('recording')).toContain(`/Recordings/${voicemail.recording}`);
    expect(mediaUrl.searchParams.get('signature')).toBeTruthy();

    const audio = await request.get(mediaUrl.toString());
    expect(audio.status()).toBe(200);
    expect(audio.headers()['content-type']).toBe('audio/mpeg');

    // The proxy fetched the .mp3 rendition of that recording from "Twilio".
    const fetched = await fakeTwilioRequests(request, 'recordings.media');
    expect(fetched.map((r) => r.url)).toContain(`${mediaUrl.searchParams.get('recording')}.mp3`);

    // The link only works with its signature.
    const tampered = new URL(mediaUrl);
    tampered.searchParams.set('recording', mediaUrl.searchParams.get('recording').replace(voicemail.recording, 'RE0000'));
    expect((await request.get(tampered.toString())).status()).toBe(403);
  });

  test('deletes a single voicemail', async ({ authenticatedPage: page, request }) => {
    const [voicemail, ...rest] = VOICEMAILS;
    const dialog = await openVoicemail(page);

    page.once('dialog', (confirm) => confirm.accept());
    const deleted = page.waitForResponse((r) => r.request().method() === 'DELETE' && r.url().includes(`/api/v1/voicemail/${voicemail.callsid}`));
    // Play, then Delete; the icons carry no accessible name of their own.
    await voicemailRow(dialog, voicemail).getByRole('button').last().click();
    expect((await deleted).ok()).toBeTruthy();

    await expect(dialog.getByRole('alert')).toContainText('Voicemail recording permanently deleted from Twilio');
    await expect(voicemailRow(dialog, voicemail)).toHaveCount(0);
    for (const other of rest) {
      await expect(voicemailRow(dialog, other)).toHaveCount(1);
    }

    const twilioDeletes = await fakeTwilioRequests(request, 'recordings.delete');
    expect(twilioDeletes.map((r) => r.url)).toEqual([expect.stringContaining(`/Recordings/${voicemail.recording}.json`)]);
  });

  test('bulk deletes the selected voicemails', async ({ authenticatedPage: page, request }) => {
    const remaining = VOICEMAILS.slice(1);
    const dialog = await openVoicemail(page);
    await expect(voicemailRow(dialog, remaining[0])).toHaveCount(1);

    await dialog.getByRole('checkbox', { name: 'Select all voicemails' }).check();
    for (const voicemail of remaining) {
      await expect(dialog.getByRole('checkbox', { name: `Select voicemail ${voicemail.callsid}` })).toBeChecked();
    }

    page.once('dialog', (confirm) => {
      expect(confirm.message()).toContain(`Permanently delete ${remaining.length} voicemail recordings`);
      confirm.accept();
    });
    await dialog.getByRole('button', { name: 'Delete selected' }).click();

    await expect(dialog.getByRole('alert')).toContainText('Voicemail recordings permanently deleted from Twilio');
    await expect(dialog.getByText('No voicemails found')).toBeVisible();

    const twilioDeletes = (await fakeTwilioRequests(request, 'recordings.delete')).map((r) => r.url);
    for (const voicemail of remaining) {
      expect(twilioDeletes).toContainEqual(expect.stringContaining(`/Recordings/${voicemail.recording}.json`));
    }
  });

  test('deleted voicemails stay gone after a reload', async ({ authenticatedPage: page }) => {
    await page.reload();
    const dialog = await openVoicemail(page);
    await expect(dialog.getByText('No voicemails found')).toBeVisible();
  });
});
