# Users

---

User authentication is primarily sourced from a BMLT root server. You can also create users in Yap and use Yap's internal authentication. The admin **Users** page (`/admin/users`) lets administrators add, edit, and delete local users when logged in with admin privileges.

## User IDs

`users.id` is an integer auto-increment primary key (unchanged from 4.5.x). **Usernames** are the stable identifier for local accounts in the admin UI and API. Prefer creating users through the admin UI rather than raw SQL.

## Creating an admin via SQL

To bootstrap the first admin user, run the following MySQL script. Set a strong password before executing on your instance:

```sql
SET @realname = '';
SET @username = '';
SET @password = '';
INSERT INTO users (name, username, password, permissions, is_admin) VALUES (@realname, @username, SHA2(@password, 256), 0, 1);
```

Once you log in with that admin user, you can create and manage additional users from the admin UI.

## Password reset

Passwords are stored as one-way SHA-256 hashes and cannot be recovered. Reset with:

```sql
SET @username = '';
SET @newpassword = '';
UPDATE users set password = SHA2(@newpassword, 256) where username = @username;
```

Authenticated users can also change their password from the account menu in the admin SPA.

## Admin UI preferences

The admin SPA stores your **preferred language** in browser `localStorage` (`preferredLanguage`). This affects UI labels on login and throughout the portal. BMLT-sourced users still authenticate against the root server; local users authenticate against Yap's `users` table.

## Permissions

The `permissions` column controls feature access (for example, "Manage Users"). The `is_admin` flag grants full administrative access including the Users page and system-wide settings.
