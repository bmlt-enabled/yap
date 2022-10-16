# Using Hidden Service Bodies For Helpline Lookups
---

It is possible to create a service body with an unpublished group in order create additional routing for service bodies that may not exist in a given root server.

Once those service bodies have been populated and the unpublished meetings are added, you can make use of the helpline field to route calls.

You will also need to add to the config.php three additional variables.  This allows yap to authenticate to the root server and retrieve the unpublished meetings.  This is required as a BMLT root server by design will not return unpublished meetings in the semantic interface.

```php
static $helpline_search_unpublished = true;
static $bmlt_username = "";
static $bmlt_password = "";
```

You will need to also ensure that PHP has write access to write to this folder, in order to store the authentication cookie from the BMLT root server.

**NOTE: This will not work for the Tomato server, because there is no concept of authentication.**
