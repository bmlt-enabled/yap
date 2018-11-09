---
layout: default
title: Home
nav_order: 1
permalink: /
---


# Yap
{: .fs-9 }

---


Requires a minimum of PHP 5.6 to use.

The purposes of yap are :
* To be able to take the results of a BMLT root server and play it back through the telephone.  
* To be able to set up routing rules for zip codes and helpline numbers with optional extension dialing.

Unlike it's predecessor bmlt-vox, this doesn't require any special infrastructure other than a server capable of delivering PHP over HTTP(S).

We are taking advantage of using Twilio which essentially handles all the VOIP parts.  You provision a number, set up an application, and point it your PHP server.

{: .fs-6 .fw-300 }

[View on GitHub](http://github.com/radius314/yap){: .btn .fs-5 }
