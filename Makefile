.PHONY: upgrade

run:
	composer install
	yarn install
	docker-compose up --build

lint:
	find . -name "*.php" ! -path '*/vendor/*' -print0 | xargs -0 -n1 -P8 php -l
	composer install --dev
	vendor/squizlabs/php_codesniffer/bin/phpcs --warning-severity=6 --standard=PSR2 --ignore=vendor --extensions=php ./

lint-fix: lint
	vendor/squizlabs/php_codesniffer/bin/phpcbf --warning-severity=6 --standard=PSR2 --ignore=vendor --extensions=php --report=summary ./

upgrade:
	./upgrade.sh

simulate:
	ngrok http 3100

debug:
	docker-compose up --build

test:
	composer test

bundle-deps:
	yarn install
	yarn global add gulp-cli

bundle:
	gulp

watch:
	gulp watch

deploy: bundle-deps bundle
