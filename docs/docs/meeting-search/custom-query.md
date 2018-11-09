---
layout: default
title: Custom Query
nav_order: 8
parent: Meeting Search
---

## Custom Query

---


In some cases you may want use a custom BMLT query.  For example, if you have a small service body you may want to ignore the day of the week concept that is the default behavior in searches.

You can do this with the setting `custom_query`.  This setting also supports the use of some magic variables.

For example say you want to always use the service body id for making queries, you could create the settings as follows:

`static $custom_query="&services[]={SETTING_SERVICE_BODY_ID}"`

Because there is a setting called `service_body_id` already and assuming you had overridden it, meeting searches will now send a query to the BMLT and return accordingly.  

You could have also hardcoded it if you wanted.  Like any other variable, you can set this on the querystring as a session wide override.

In some cases you may need to combine this with the `result_count_max` to increase the limit of how many results are returned.  You may also need to use `sms_ask`, as many results could be returned.

There are a couple of other stock magic variables.

1. `{DAY}` - will use the day of today / tomorrow.
2. `{LATITUDE}` - the latitude of the lookup.
3. `{LONGITUDE}` - the longitude of the lookup.

If you do not have `{LATITUDE}` or `{LONGITUDE}` in your custom query, it will automatically skip the location gathering aspects of the meeting search menus and go directly to returning results. 
