.PHONY: run
run:
	cd src; composer install
	cd src; npm install
	cd src; env ENVIRONMENT=test php artisan serve

.PHONY: lint
lint:
	cd src; find . -name "*.php" ! -path '*/vendor/*' -print0 | xargs -0 -n1 -P8 php -l
	cd src; composer install
	cd src; vendor/squizlabs/php_codesniffer/bin/phpcs

.PHONY: lint-fix
lint-fix:
	cd src; vendor/squizlabs/php_codesniffer/bin/phpcbf

.PHONY: simulate
simulate:
	ngrok http 3100

.PHONY: debug
debug:
	cd docker; docker-compose up --build

.PHONY: test
test:
	cd src; php artisan test

.PHONY: bundle-deps
bundle-deps:
	cd src; npm install

.PHONY: bundle
bundle:
	cd src; gulp

.PHONY: watch
watch:
	cd src; gulp watch

.PHONY: coverage-xml
coverage-xml:
	cd src; XDEBUG_MODE=coverage vendor/bin/pest --coverage --coverage-clover coverage.xml

.PHONY: coverage
coverage:
	cd src; XDEBUG_MODE=coverage vendor/bin/pest --coverage --coverage-html tests/reports/coverage

.PHONY: cache-clear
cache-clear:
	cd src; php artisan route:clear
	cd src; php artisan cache:clear
	cd src; php artisan config:clear
	cd src; php artisan view:clear

.PHONY: deploy
deploy: bundle-deps bundle

.PHONY: swagger
swagger:
	cd src; php artisan l5-swagger:generate
