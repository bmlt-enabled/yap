.PHONY: upgrade

upgrade:
	mv config.php ../
	git pull origin master
	mv ../config.php .
