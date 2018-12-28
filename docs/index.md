---
layout: default
title: Home
nav_order: 1
permalink: /
---


# Yap
{: .fs-9 }

---


Recommended to use PHP 7.2 or greater.  PHP 5.6 is going to be end of life on December 31, 2018 and cannot guarantee compatibility after that.

Yap 3.x requires MySQL 5.5 or greater and an Apache-based webserver with mod_rewrite enabled.


The purposes of yap are :
* To be able to take the results of a BMLT root server and play it back through the telephone.  
* To be able to set up routing rules for zip codes and helpline numbers with optional extension dialing.

Unlike it's predecessor bmlt-vox, this doesn't require any special infrastructure other than a server capable of delivering PHP over HTTP(S).

We are taking advantage of using Twilio which essentially handles all the VOIP parts.  You provision a number, set up an application, and point it your PHP server.

{: .fs-6 .fw-300 }

[View on GitHub](http://github.com/bmlt-enabled/yap){: .btn .fs-5 }
