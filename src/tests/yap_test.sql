SET @realname = 'admin';
SET @username = 'admin';
SET @password = 'admin';
INSERT INTO users (name, username, password, permissions, is_admin) VALUES (@realname, @username, SHA2(@password, 256), 0, 1);
