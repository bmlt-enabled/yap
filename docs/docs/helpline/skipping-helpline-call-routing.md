---
title: Skipping Helpline Call Routing
sidebar_position: 10
---

---

When configuring the TwiML app instead of pointing to `index.php` point to `input-method.php?Digits=2`.

If you still want the title to display also point to `input-method.php?Digits=2&PlayTitle=1`.

This could useful for wiring up to a Grasshopper extension.  Typically you set this as Department Extension and have your prompt instruct to press a series of keypresses.

For example, if you set this up as extension 1, from within you employee extensions you would instruct the caller to press *1 (star one) for finding meetings.  
