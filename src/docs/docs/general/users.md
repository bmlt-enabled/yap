# Users

---

User authentication has primarily been sourced by a BMLT. You can create users outside the BMLT and use Yap’s internal user authentication. To create an admin user run the following MySQL script. Be sure to set a strong password and fill it in the variable before running this on your MySQL instance.

Once you log in, using the admin user, you can create / manage additional users.

```sql
SET @realname = '';
SET @username = '';
SET @password = '';
INSERT INTO users (id, name, username, password, permissions, is_admin) VALUES (UUID(), @realname, @username, SHA2(@password, 256), 0, 1);
```

If you happen to forget your password, or need to reset it for some reason.  You can run this query to reset it to something new. (Note: it is not possible to recover a password, as they are stored as one-way hashes).

```sql
SET @username = '';
SET @newpassword = '';
UPDATE users set password = SHA2(@newpassword, 256) where username = @username;
```
