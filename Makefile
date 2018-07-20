.PHONY: upgrade

lint:
	find . -name '*.php' -exec php -l {} \;

upgrade:
	mv config.php ../
	git pull origin unstable
	mv ../config.php .

simulate:
	ngrok http 3100
