PHP_SERVICE=xmedia-app

composer:
	docker container exec -it $(PHP_SERVICE) composer install

# Code Quality
phpcs:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpcs --standard=PSR12 src tests

phpcbf:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpcbf --standard=PSR12 src tests

phpstan:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpstan analyse src tests --memory-limit=2G

phpstan-changed:
	@echo "Running PHPStan on changed files..."
	@git diff --name-only --diff-filter=AM | grep '\.php$$' | xargs -I {} docker container exec -it $(PHP_SERVICE) vendor/bin/phpstan analyse {} --memory-limit=2G || true

# Testing
test:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpunit

test-unit:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpunit --testsuite="Unit Tests"

# Test by groups
test-critical:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpunit --group=critical

test-service:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpunit --group=service

test-entity:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpunit --group=entity

test-order:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpunit --group=order

test-email:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpunit --group=email

# Test specific services
test-order-service:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpunit tests/Unit/Service/Order/

test-comment-service:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpunit tests/Unit/Service/Comment/

test-feedback-service:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpunit tests/Unit/Service/Feedback/

test-integration:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpunit --testsuite="Integration Tests"

test-functional:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpunit --testsuite="Functional Tests"

test-smoke:
	@echo "⚠️  Smoke tests require test database setup. See TEST_DATABASE_SETUP.md"
	@echo "Run: make db-test-setup first"
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpunit --group=smoke

test-coverage:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpunit --coverage-html var/coverage --testsuite="Unit Tests"

test-verbose:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpunit --verbose --testsuite="Unit Tests"

# Combined Commands
qa: phpcs phpstan test-unit

qa-quick: phpstan-changed test-unit

# Database Management
db-test-create:
	docker container exec -it $(PHP_SERVICE) php bin/console doctrine:database:create --env=test

db-test-migrate:
	docker container exec -it $(PHP_SERVICE) php bin/console doctrine:migrations:migrate --no-interaction --env=test

db-test-reset:
	docker container exec $(PHP_SERVICE) php bin/console doctrine:database:drop --force --env=test || true
	docker container exec $(PHP_SERVICE) php bin/console doctrine:database:create --env=test
	docker container exec $(PHP_SERVICE) php bin/console doctrine:migrations:migrate --no-interaction --env=test

db-test-setup:
	./bin/setup-test-db.sh

.PHONY: phpcs phpcbf phpstan phpstan-changed test test-unit test-all test-critical test-service test-entity test-order test-email test-order-service test-comment-service test-feedback-service test-integration test-functional test-smoke test-coverage test-verbose qa qa-quick db-test-create db-test-migrate db-test-reset db-test-setup
