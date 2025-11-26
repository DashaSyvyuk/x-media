PHP_SERVICE=xmedia-app

phpcs:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpcs --standard=PSR12 src

phpcbf:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpcbf --standard=PSR12 src
