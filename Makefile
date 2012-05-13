all: vendor

vendor: composer.phar
	./composer.phar update

composer.phar:
	curl -s http://getcomposer.org/installer | php
