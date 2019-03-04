.PHONY: upgrade

run:
	composer install
	docker-compose up --build

lint:
	find . -name '*.php' -exec php -l {} \;
	composer install --dev
	vendor/squizlabs/php_codesniffer/bin/phpcs --warning-severity=6 --standard=PSR2 --ignore=vendor --extensions=php --report=summary ./

lint-fix:
	vendor/squizlabs/php_codesniffer/bin/phpcbf --warning-severity=6 --standard=PSR2 --ignore=vendor --extensions=php --report=summary ./

upgrade:
	./upgrade.sh

simulate:
	ngrok http 3100

debug:
	docker-compose up --build

test:
	composer test
