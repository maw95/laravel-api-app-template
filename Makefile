build:
	docker compose up -d --build
	docker compose exec app bash -c "composer setup"

start:
	docker compose up -d

exec:
	docker compose exec app bash

cs_fix:
	docker compose exec app bash -c "vendor/bin/pint"

test:
	docker compose exec app bash -c "vendor/bin/pint --test && vendor/bin/phpstan analyse app tests --memory-limit=4G && php artisan test"

cs_fix_and_test: cs_fix test

stop:
	docker compose stop

down:
	docker compose down

restart:
	docker compose restart

clear_cache:
	docker compose exec app bash -c "php artisan config:clear"
	docker compose exec app bash -c "php artisan cache:clear"
	docker compose exec app bash -c "php artisan event:clear"
	docker compose exec app bash -c "php artisan queue:clear"
	docker compose exec app bash -c "php artisan route:clear"
	docker compose exec app bash -c "php artisan view:clear"

horizon:
	docker compose exec app bash -c "php artisan horizon"
