---
title: Force Helpline Routing
sidebar_position: 5
---

---

You can force the helpline option to go directly to a specific service body by specifying the following on your webhook in Twilio.

`?override_service_body_id=x`

The service body id would be found in your BMLT root server.  It must exist in that root server instance to be routed correctly.
