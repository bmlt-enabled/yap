.PHONY: upgrade

lint:
	find . -name '*.php' -exec php -l {} \;

upgrade:
	mv config.php ../
	git pull origin master
	mv ../config.php .

simulate:
	ngrok http 3100
