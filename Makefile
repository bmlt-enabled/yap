
.PHONY: upgrade

lint:
	find . -name '*.php' -exec php -l {} \;

upgrade:
	./upgrade.sh

simulate:
	ngrok http 3100

debug:
	docker-compose up

