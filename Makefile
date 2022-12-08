start:
	php -S localhost:8080 -t public public/index.php

composer:
	composer install
	composer update
	composer dump-autoload
	composer validate