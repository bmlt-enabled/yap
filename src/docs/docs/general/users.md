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

`SHA2(…, 256)` is an unsalted hash, so it is only a stand-in until the first login: when that admin logs in, Yap re-hashes the password with a salted hash (bcrypt) and saves it.

## How passwords are stored

Yap stores local passwords as salted bcrypt hashes (Laravel's `Hash::make()`). Passwords cannot be recovered, and two users with the same password get different hashes.

Before 5.0.0, Yap stored unsalted SHA-256 hashes. Those still work: a user with an old hash logs in normally, and Yap replaces the hash with a bcrypt one at that login. Rows that nobody logs in to keep their SHA-256 hash; reset those passwords (below) or delete the accounts if you don't want SHA-256 hashes left in the database.

BMLT users have no password in Yap. Their passwords are checked by the BMLT root server at every login.

## Password reset

Authenticated users can change their password from the account menu in the admin SPA, and admins can set a new password for any local user on the **Users** page. Both store a bcrypt hash.

If nobody can log in, reset a password with SQL. It's stored as SHA-256 until that user's next login, which re-hashes it:

```sql
SET @username = '';
SET @newpassword = '';
UPDATE users set password = SHA2(@newpassword, 256) where username = @username;
```

## Admin UI preferences

The admin SPA stores your **preferred language** in browser `localStorage` (`preferredLanguage`). This affects UI labels on login and throughout the portal. BMLT-sourced users still authenticate against the root server; local users authenticate against Yap's `users` table.

## Permissions

The `permissions` column controls feature access (for example, "Manage Users"). The `is_admin` flag grants full administrative access including the Users page and system-wide settings.
