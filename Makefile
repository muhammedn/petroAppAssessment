.PHONY: install run migrate docker-up

install:
	composer install
	cp -n .env.example .env || true
	php artisan key:generate
	touch database/database.sqlite
	php artisan migrate

run:
	php artisan serve

migrate:
	php artisan migrate

docker-up:
	docker compose up --build

