---
title: Upgrading from Yap 2.x to Yap 3.x
sidebar_position: 6
---

---

Follow the upgrade steps as you normally do above.  You will also need to follow item #8 under [Setup](../../general/setup/), to add the database configuration.

You can check that everything is functioning by going to the upgrade advisor http://your-instance/api/v1/upgrade

If you need to re-import your data from your root server you can run the following script.  This will delete any changes you have made since you upgraded.

```
TRUNCATE TABLE config;
DELETE FROM flags WHERE flag_name='root_server_data_migration';
```
