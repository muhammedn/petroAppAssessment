.PHONY: install run test migrate docker-up docker-test

install:
	composer install
	cp -n .env.example .env || true
	php artisan key:generate
	touch database/database.sqlite
	php artisan migrate

run:
	php artisan serve

test:
	php artisan config:clear
	./vendor/bin/pest --colors=always

migrate:
	php artisan migrate

docker-up:
	docker compose up --build

docker-test:
	docker compose run --rm app sh -c "composer install --dev && ./vendor/bin/pest"
