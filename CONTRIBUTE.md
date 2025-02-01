### Getting it up and running.

1. Clone this repository.
2. Run `npm install`. 
3. Run `composer install`
3. Run `make bundle`. 
4. Run `make serve`. 
5. Browse to http://localhost:8000/yap in your web browser.

#### Logging in to the admin interface

1. Browse to http://localhost:8000/yap/admin
2. Create an admin user in the database

```sql
INSERT INTO users (id, name, username, password, permissions, is_admin) VALUES (UUID(), 'admin', 'admin', SHA2('admin', 256), 0, 1);
```

3. Log with username: `admin`, password: `admin`.

### Testing

After cloning, add a file called `.env.testing` with the value `GOOGLE_MAPS_API_KEY=<value>`.  Then run `make test`.

To run code coverage, you can run `make coverage`.

### Seeing 500 errors during tests.

Use `$this->withoutExceptionHandling()` in your test classes to see the underlying 500 error during your tests.

### Docs

1. Go to `cd src/docs`
2. Run `npm run start`

You should be able to see the documents update as you edit files at http://localhost:3000.

To test building the docs run:

```shell
npm run docusaurus build
```

### API Docs (WIP)

This only works locally right now.

1. Run `make swagger`.
2. Browse to `/api/documentation`
