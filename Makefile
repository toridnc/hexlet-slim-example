install:
	composer install

start:
	php -S localhost:8080 -t public public/index.php

lint:
	composer exec --verbose phpcs -- --standard=PSR12 public src templates