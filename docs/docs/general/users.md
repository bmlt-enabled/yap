# Users

---

User authentication has primarily been sourced by a BMLT. You can create users outside the BMLT and use Yap’s internal user authentication. To create an admin user run the following MySQL script. Be sure to set a strong password and fill it in the variable before running this on your MySQL instance.

Once you log in, using the admin user, you can create / manage additional users.

```
SET @realname = '';
SET @username = '';
SET @password = '';
INSERT INTO users (name, username, password, permissions, is_admin) VALUES (@realname, @username, SHA2(@password, 256), 0, 1);
```
