---
sidebar_position: 4
---

# Reports

The admin **Reports** page shows call detail records (CDRs) collected from Twilio status callbacks. Accurate reporting requires each Twilio phone number to post status changes to your Yap `status.php` webhook.

## Call detail records

When a call completes, Twilio sends status events to `/status.php`. Yap stores these as CDR rows you can filter and export from the Reports page. If status callbacks are missing for a number, calls may complete normally but will not appear in reports — the upgrade advisor warns about misconfigured numbers.

Configure the **Status Callback URL** on each Twilio phone number to point at `https://your-yap-host/status.php` (or the equivalent path if Yap lives in a subdirectory).

## Missed calls

An **answered** call is when a volunteer accepts a call and successfully connects to the caller.

A **missed** call occurs when a volunteer did not successfully answer. There is a record of the call being rejected or going unanswered.

## Map metrics

The map view plots call volume by area code or region, useful for spotting coverage gaps or misrouted traffic.

## API access

Authenticated admins can query CDR data via `GET /api/v1/reports/cdr` (see API documentation at `/api/v1/documentation`).
