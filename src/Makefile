.PHONY: run
run: serve
	composer install
	npm install

.PHONY: serve
serve:
	env ENVIRONMENT=dev php -dxdebug.mode=debug -dxdebug.start_with_request=yes -S localhost:8000 -t . server.php

// To run a specific test, use the following command: make test-debug test=SmsGatewayTest
.PHONY: test-debug
test-debug:
	php -dxdebug.mode=debug -dxdebug.start_with_request=yes -dxdebug.client_port=9003 vendor/bin/pest tests/Feature/$(test).php

.PHONY: e2e-test
e2e-test:
	env CYPRESS_BASE_URL=http://localhost:8000 npx cypress run

.PHONY: e2e-test-ui
e2e-test-ui:
	env CYPRESS_BASE_URL=http://127.0.0.1:8000/yap npx cypress open --component

.PHONY: lint
lint:
	find . -name "*.php" ! -path '*/vendor/*' -print0 | xargs -0 -n1 -P8 php -l
	composer install
	vendor/squizlabs/php_codesniffer/bin/phpcs

.PHONY: lint-fix
lint-fix:
	vendor/squizlabs/php_codesniffer/bin/phpcbf

.PHONY: simulate
simulate:
	ngrok http 3100

.PHONY: test
test:
	php -dxdebug.mode=debug -dxdebug.start_with_request=yes -dxdebug.client_port=9003 vendor/bin/pest

.PHONY: coverage-xml
coverage-xml:
	XDEBUG_MODE=coverage vendor/bin/pest --coverage --coverage-clover coverage.xml

.PHONY: coverage
coverage:
	XDEBUG_MODE=coverage vendor/bin/pest --coverage --coverage-html tests/reports/coverage

.PHONY: cache-clear
cache-clear:
	php artisan route:clear
	php artisan cache:clear
	php artisan config:clear
	php artisan view:clear

.PHONY: deploy
deploy:
	npm install
	npm run build

.PHONY: swagger
swagger:
	php artisan l5-swagger:generate

.PHONY: seed
seed:
	ENVIRONMENT=test php artisan db:seed

mix:
	npm run watch
