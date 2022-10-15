.PHONY: run lint lint-fix upgrade simulate debug test bundle bundle-deps watch debug

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
	vendor/pestphp/pest/bin/pest --configuration phpunit.xml tests

bundle-deps:
	yarn install
	yarn global add gulp-cli

bundle:
	gulp

watch:
	gulp watch

cache-clear:
	php artisan route:clear
	php artisan cache:clear
	php artisan config:clear
	php artisan view:clear

deploy: bundle-deps bundle
