<h1 align="center">yap</h1>
<h1 align="center">
<a href="https://github.com/bmlt-enabled/yap/releases/latest"><img src="https://img.shields.io/github/v/release/bmlt-enabled/yap"></a>
<a href="https://php.net"><img src="https://img.shields.io/badge/php-%5E8.1-%238892BF" alt="PHP Programming Language"></a>
<a href="https://github.com/bmlt-enabled/yap/actions/workflows/unstable.yml"><img src="https://img.shields.io/github/actions/workflow/status/bmlt-enabled/yap/unstable.yml?branch=main" alt="Build Status"></a>
<a href="https://raw.githubusercontent.com/bmlt-enabled/yap/main/LICENSE"><img src="https://img.shields.io/github/license/bmlt-enabled/yap"></a>
<a href="https://codecov.io/gh/bmlt-enabled/yap" > 

 <img src="https://codecov.io/gh/bmlt-enabled/yap/branch/main/graph/badge.svg?token=9LZWU5WY7C"/> 
 </a>
<a href="https://github.com/bmlt-enabled/yap/releases"><img src="https://img.shields.io/github/downloads/bmlt-enabled/yap/total"></a>
</h1>

The purposes of yap are :
* To be able to take the results of a BMLT root server and play it back through the telephone.  
* To be able to set up routing rules for zip codes and helpline numbers with optional extension dialing.

We are taking advantage of using Twilio which essentially handles all the VOIP parts.  You provision a number, set up an application, and point it your PHP server.

# Documentation

For setup instructions and general documentation please visit [https://yap.bmlt.app](https://yap.bmlt.app)

# Downloads

* Latest Releases: [https://github.com/bmlt-enabled/yap/releases](https://github.com/bmlt-enabled/yap/releases)
* Bleeding Edge: [https://archives.bmlt.app/index.html#yap](https://archives.bmlt.app/index.html#yap)

# ⚠️ Major Refactor

Currently we are in the process of doing a major overhaul of the codebase.  This will improve the stability and maintainability.  Also, as part of this change, we are making the code more testable which will help us track code paths more effectively.  All the PHP code has been migrated, however there are some remaining database components that are not in a Laravel-like structure.

In a future release we will refactor the frontend by building a new React application to consume the rebuilt APIs, that work has partially started but has been put on pause.

# API Docs (WIP)

This only works locally right now.

1. Run `make swagger`.
2. Browse to `/api/documentation`

# Testing

After cloning, add a file called `.env.testing` with the value `GOOGLE_MAPS_API_KEY=<value>`.  Then run `make test`.

To run code coverage, you can run `make coverage`.
