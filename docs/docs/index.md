---
id: index
slug: /
sidebar_position: 1
---

# Yap

Recommended to use PHP 8.0 or greater.

Yap requires MySQL 5.5 or greater and an Apache-based webserver with `mod_rewrite` enabled.


The purposes of yap are :
* To be able to take the results of a BMLT root server and play it back through the telephone.  
* To be able to set up routing rules for zip codes and helpline numbers with optional extension dialing.
* To be able to set up volunteer schedules and associated rules to answer calls.

We are taking advantage of using Twilio which essentially handles all the VOIP parts.  You provision a number, set up an application, and point it your PHP server.
