# Volunteer Responder

---

The **Responder** option marks an individual volunteer as willing to receive follow-up notifications (SMS links to voicemail recordings, and similar outbound messages) and, in some flows, as eligible when routing specifically asks for responders.

Responder is **not** a call strategy. It is a per-volunteer checkbox on each volunteer card (or on volunteers inside a [volunteer group](./volunteer-groups)).

## Enable responder for a volunteer

1. Open **Volunteers** (or edit volunteers inside a group on the **Groups** tab).
2. Expand the volunteer card.
3. Check **Responder**.
4. **Save Volunteers**.

In CSV exports the column is `responder` (`0` = off, `1` = on).

## Voicemail notifications

When a caller leaves voicemail, Yap can SMS a link to the recording. Volunteers with **Responder** enabled are included in that notification pool alongside the **Primary Contact Number** configured under **Service Bodies → Configure → Call Handling**. See [Voicemail](./voicemail) for email and primary-contact options.

## Routing filters

Some routing paths filter the active volunteer pool by responder status—for example when only responders should receive a voicemail-related SMS leg. Volunteers without **Responder** enabled are skipped when that filter is active. Normal helpline outdial to answer live calls does not require responder unless your configuration or gender/language routing combines with a responder requirement in that call leg.

## Related topics

- [Voicemail](./voicemail) — recordings and notification setup
- [Volunteer routing](./volunteer-routing) — dial order and call strategies
- [Volunteer groups](./volunteer-groups) — shared rosters that include responder flags per member
