.PHONY: run lint lint-fix upgrade simulate debug test bundle bundle-deps watch debug coverage coverage-xml

run:
	composer install
	yarn install
	docker-compose up --build

lint:
	find . -name "*.php" ! -path '*/vendor/*' -print0 | xargs -0 -n1 -P8 php -l
	composer install
	vendor/squizlabs/php_codesniffer/bin/phpcs

lint-fix: lint
	vendor/squizlabs/php_codesniffer/bin/phpcbf

upgrade:
	./upgrade.sh

simulate:
	ngrok http 3100

debug:
	docker-compose up --build

test-deps:
	pecl install xdebug-3.1.5

test:
	php artisan test

bundle-deps:
	yarn install
	yarn global add gulp-cli

bundle:
	gulp

watch:
	gulp watch

coverage-xml:
	XDEBUG_MODE=coverage vendor/bin/pest --coverage --coverage-clover coverage.xml

coverage:
	XDEBUG_MODE=coverage vendor/bin/pest --coverage --coverage-html tests/reports/coverage

cache-clear:
	php artisan route:clear
	php artisan cache:clear
	php artisan config:clear
	php artisan view:clear

deploy: bundle-deps bundle
