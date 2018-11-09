---
layout: default
title: Tomato Helpline Routing
nav_order: 1
parent: Helpline / Volunteer Routing
---

## Tomato Helpline Routing

---


In some cases you might want make use of aggregated helpline information.  This might be a bit nuanced, but it exists.  

One thing to note about this setting, is that Tomato is not able to get service body configuration, so it will be limited to helpline field routing only.

You can use this in your config.php, however keep in mind that this would break your admin portal.  Typically this would be used with a webhook as such.

`override_tomato_helpline_routing=true`
