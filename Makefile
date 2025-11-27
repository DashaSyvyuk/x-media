PHP_SERVICE=xmedia-app

# Code Quality
phpcs:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpcs --standard=PSR12 src

phpcbf:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpcbf --standard=PSR12 src

phpstan:
	docker container exec -it $(PHP_SERVICE) vendor/bin/phpstan analyse src --memory-limit=2G

phpstan-changed:
	@echo "Running PHPStan on changed files..."
	@git diff --name-only --diff-filter=AM | grep '\.php$$' | xargs -I {} docker container exec -it $(PHP_SERVICE) vendor/bin/phpstan analyse {} --memory-limit=2G || true

# Testing
# Note: Using phpunit-clean wrapper to filter PHPUnit 9.5 deprecation warnings on PHP 8.4
# Unit tests don't require database setup
test:
	docker container exec -it $(PHP_SERVICE) ./bin/phpunit-clean --testsuite="Unit Tests"

test-unit:
	docker container exec -it $(PHP_SERVICE) ./bin/phpunit-clean --testsuite="Unit Tests"

test-all:
	docker container exec -it $(PHP_SERVICE) ./bin/phpunit-clean

test-integration:
	@echo "⚠️  Integration tests require test database setup. See TEST_DATABASE_SETUP.md"
	@echo "Run: make db-test-setup first"
	docker container exec -it $(PHP_SERVICE) ./bin/phpunit-clean --testsuite="Integration Tests"

test-functional:
	@echo "⚠️  Functional tests require test database setup. See TEST_DATABASE_SETUP.md"
	@echo "Run: make db-test-setup first"
	docker container exec -it $(PHP_SERVICE) ./bin/phpunit-clean --testsuite="Functional Tests"

test-smoke:
	@echo "⚠️  Smoke tests require test database setup. See TEST_DATABASE_SETUP.md"
	@echo "Run: make db-test-setup first"
	docker container exec -it $(PHP_SERVICE) ./bin/phpunit-clean --group=smoke

test-coverage:
	docker container exec -it $(PHP_SERVICE) ./bin/phpunit-clean --coverage-html var/coverage --testsuite="Unit Tests"

test-verbose:
	docker container exec -it $(PHP_SERVICE) ./bin/phpunit-clean --verbose --testsuite="Unit Tests"

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

.PHONY: phpcs phpcbf phpstan phpstan-changed test test-unit test-all test-integration test-functional test-smoke test-coverage test-verbose qa qa-quick db-test-create db-test-migrate db-test-reset db-test-setup
