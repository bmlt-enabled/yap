### Getting it up and running.

1. Clone this repository.
2. Run `npm install`. 
3. Run `composer install`
3. Run `make bundle`. 
4. Run `make serve`. 
5. Browse to http://localhost:8080/yap in your web browser.

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
