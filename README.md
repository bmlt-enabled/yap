<h1 align="center">yap</h1>
<h1 align="center">
<a href="https://github.com/bmlt-enabled/yap/releases/latest"><img src="https://img.shields.io/github/v/release/bmlt-enabled/yap"></a>
<a href="https://php.net"><img src="https://img.shields.io/packagist/php-v/ramsey/uuid.svg?style=flat-square&colorB=%238892BF" alt="PHP Programming Language"></a>
<a href="https://github.com/bmlt-enabled/yap/actions/workflows/unstable.yml"><img src="https://img.shields.io/github/workflow/status/bmlt-enabled/yap/unstable/master?logo=github&style=flat-square" alt="Build Status"></a>
<a href="https://raw.githubusercontent.com/bmlt-enabled/yap/master/LICENSE"><img src="https://img.shields.io/github/license/bmlt-enabled/yap"></a>
<a href="https://github.com/bmlt-enabled/yap/releases"><img src="https://img.shields.io/github/downloads/bmlt-enabled/yap/total"></a>
</h1>

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
