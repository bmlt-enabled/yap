<h1 align="center">yap</h1>
<h1 align="center">
<a href="https://github.com/bmlt-enabled/yap/releases/latest"><img src="https://img.shields.io/github/v/release/bmlt-enabled/yap"></a>
<a href="https://php.net"><img src="https://img.shields.io/packagist/php-v/ramsey/uuid.svg?style=flat-square&colorB=%238892BF" alt="PHP Programming Language"></a>
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

<img src="https://img.shields.io/badge/legacyleft-565-red"/> 

```shell
make legacy
```
Currently we are in the process of doing a major overhaul of the codebase, by migrating all of the legacy PHP to a more Laravel-like structure.  This will improve the stability and maintainability.  Also as part of this change, we are making the code more testable which will help us track code paths more effectively.  We also plan to redo the frontend part of it migrating from a Bootstrap/Jquery structure to React.

The current status is tht all the non-admin PHP has been migrated to controllers.  The next step will be to migrate the admin components.  Once this is completed, there will be some other include type files that need to be migrated, namely functions.php which is a general location for all php functions in the `legacy` structure.   Once this is all completed, we would cut a version (either 4.3.0 or 5.0.0, uncertain right now).

In a future release we will refactor the frontend by building a new React application to consume the rebuilt APIs, that work has partially started but has been put on pause.

# API Docs (WIP)

This only works locally right now.

1. Run `make swagger`.
2. Browse to `/api/documentation`
