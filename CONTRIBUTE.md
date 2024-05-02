### Getting it up and running.

1. Clone this repository.
2. Run `make deploy`. 
3. Run `make watch`. 
4. Run `make run`. 
5. Browse to http://localhost:3100/yap in your web browser.

### Testing

After cloning, add a file called `.env.testing` with the value `GOOGLE_MAPS_API_KEY=<value>`.  Then run `make test`.

To run code coverage, you can run `make coverage`.

### Seeing 500 errors during tests.

Use `$this->withoutExceptionHandling()` in your test classes to see the underlying 500 error during your tests.

### API Docs (WIP)

This only works locally right now.

1. Run `make swagger`.
2. Browse to `/api/documentation`
