# Volunteer Groups

---

Volunteer groups let you define a reusable roster and schedule once, then dial every member of that group from one slot on the **Volunteers** routing list. Groups can also be shared with other service bodies so multiple areas use the same volunteer pool without duplicating shift data.

## When to use groups

Use a group when:

- Several volunteers always cover the same shift pattern together (for example a regional backup team).
- Multiple service bodies should route to the same volunteers (for example a parent area and its sub-areas).
- You want one entry on the dial list instead of many individual cards for the same team.

Groups are optional. Individual volunteers on the **Volunteers** tab work the same way they always have.

## Create and manage a group

1. Log in to the admin portal at `https://your-yap-instance/admin`.
2. Open the **Groups** tab.
3. Select a service body from the dropdown.
4. Click **Add Group** and enter a **Group name**.
5. Under **Shared service bodies**, choose any other service bodies that may include this group on their volunteer routing list. The owning service body is always included.
6. Save the group.
7. With the group selected, use the **Volunteers** section below the group dropdown to add volunteers, shifts, and phone numbers—the same controls as on the main **Volunteers** tab, but stored against the group.

Groups are managed through the REST API as well (`GET/POST/PUT/DELETE /api/v1/groups`).

## Add a group to volunteer routing

Groups do not dial automatically. You must place them on the routing list for a service body:

1. Open the **Volunteers** tab and select the service body.
2. Click **Include Group** (or equivalent group action).
3. Pick the group from the list and confirm.
4. A **GROUP** card appears on the routing list. Enable it and drag it to set dial order relative to individual volunteers.
5. **Save Volunteers**.

When that group card is enabled and active, Yap expands it to the group's members who are on shift at call time. Call strategy (linear cycle, blasting, and so on) applies to the expanded list—see [Call strategies](./call-strategies).

## Shared service bodies

**Shared service bodies** on the group definition control which service bodies can *reference* the group on their **Volunteers** tab. Volunteers and shifts live on the group itself; sharing does not copy data into each service body.

A service body that shares a group still sets its own dial order and call-handling options under **Service Bodies → Configure**.

## Related topics

- [Volunteer routing](./volunteer-routing) — initial setup and routing overview
- [Call strategies](./call-strategies) — how blasting and cycling interact with group members
- [Volunteer responder](./volunteer-responder) — per-volunteer responder flag inside a group
