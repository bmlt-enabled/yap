[![Build Status](https://travis-ci.org/bmlt-enabled/yap.svg?branch=master)](https://travis-ci.org/bmlt-enabled/yap)
# yap

The purposes of yap are :
* To be able to take the results of a BMLT root server and play it back through the telephone.  
* To be able to set up routing rules for zip codes and helpline numbers with optional extension dialing.

We are taking advantage of using Twilio which essentially handles all the VOIP parts.  You provision a number, set up an application, and point it your PHP server.

# Documentation

For setup instructions and general documentation please visit [https://bmlt.app/yap](https://bmlt.app/yap)

# Downloads

* Latest Releases: [https://github.com/bmlt-enabled/yap/releases](https://github.com/bmlt-enabled/yap/releases)
* Bleeding Edge: [https://archives.bmlt.app/index.html#yap](https://archives.bmlt.app/index.html#yap)

# Upgrading to Yap 4.x

You will need to in addition to copying `config.php` over make a new file called `.env` and set `APP_KEY=base64:<secret>`.  You can generate your unique secret here, setting the length to 32: https://generate.plus/en/base64.  

If you do not set one a default one will be used.  
