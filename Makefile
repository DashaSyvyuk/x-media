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
test:
	docker container exec -it $(PHP_SERVICE) ./bin/phpunit

test-unit:
	docker container exec -it $(PHP_SERVICE) ./bin/phpunit --testsuite="Unit Tests"

test-integration:
	docker container exec -it $(PHP_SERVICE) ./bin/phpunit --testsuite="Integration Tests"

test-functional:
	docker container exec -it $(PHP_SERVICE) ./bin/phpunit --testsuite="Functional Tests"

test-smoke:
	docker container exec -it $(PHP_SERVICE) ./bin/phpunit --group=smoke

test-coverage:
	docker container exec -it $(PHP_SERVICE) ./bin/phpunit --coverage-html var/coverage

test-verbose:
	docker container exec -it $(PHP_SERVICE) ./bin/phpunit --verbose

# Combined Commands
qa: phpcs phpstan test

qa-quick: phpstan-changed test-smoke

.PHONY: phpcs phpcbf phpstan phpstan-changed test test-unit test-integration test-functional test-smoke test-coverage test-verbose qa qa-quick
