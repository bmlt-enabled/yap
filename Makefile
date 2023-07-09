.PHONY: run
run:
	composer install
	yarn install
	docker-compose up --build

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

.PHONY: debug
debug:
	docker-compose up --build

.PHONY: test-deps
test-deps:
	pecl install xdebug-3.1.5

.PHONY: test
test:
	php artisan test

.PHONY: bundle-deps
bundle-deps:
	yarn install
	yarn global add gulp-cli

.PHONY: bundle
bundle:
	gulp

.PHONY: watch
watch:
	gulp watch

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
deploy: bundle-deps bundle

.PHONY: swagger
swagger:
	php artisan l5-swagger:generate
