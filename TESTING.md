# Testing Guide for X-Media Project

This document provides comprehensive instructions for running tests and static code analysis in the X-Media project.

## Table of Contents

1. [Overview](#overview)
2. [Test Types](#test-types)
3. [Running Tests](#running-tests)
4. [Static Code Analysis](#static-code-analysis)
5. [Pre-commit Hooks](#pre-commit-hooks)
6. [CI/CD Integration](#cicd-integration)
7. [Writing Tests](#writing-tests)

## Overview

The X-Media project uses a comprehensive testing strategy that includes:

- **Unit Tests**: Test individual classes and methods in isolation
- **Integration Tests**: Test database interactions and repository methods
- **Functional Tests**: Test controller endpoints and HTTP responses
- **Smoke Tests**: Quick tests to verify critical functionality

### Test Statistics

- Total test files: 7
- Total test methods: 45+
- Test categories: Unit, Integration, Functional, Smoke

## Test Types

### Unit Tests

Located in `tests/Unit/`, these tests verify individual components without external dependencies.

**What we test:**
- Entity classes (Order, Product)
- Utility classes (OrderNumber)
- Business logic methods
- Data transformations

**Example test files:**
- `tests/Unit/Entity/OrderTest.php`
- `tests/Unit/Entity/ProductTest.php`
- `tests/Unit/Utils/OrderNumberTest.php`

### Integration Tests

Located in `tests/Integration/`, these tests verify database interactions and repository operations.

**What we test:**
- Repository CRUD operations
- Database queries
- Data persistence
- Entity relationships

**Example test files:**
- `tests/Integration/Repository/OrderRepositoryTest.php`
- `tests/Integration/Repository/ProductRepositoryTest.php`

### Functional Tests

Located in `tests/Functional/`, these tests verify complete HTTP request/response cycles.

**What we test:**
- Controller endpoints
- HTTP status codes
- Page accessibility
- Response content

**Example test files:**
- `tests/Functional/Controller/HomePageControllerTest.php`
- `tests/Functional/Controller/ProductPageControllerTest.php`
- `tests/Functional/Controller/CategoryPageControllerTest.php`
- `tests/Functional/Controller/StaticPagesControllerTest.php`

### Smoke Tests

Quick tests tagged with `@group smoke` that verify critical functionality.

**What we test:**
- Homepage accessibility
- Product page accessibility
- Category page accessibility
- Static pages accessibility

## Running Tests

### Prerequisites

Ensure your development environment is running:

```bash
docker-compose up -d
```

### Using Makefile (Recommended)

#### Run All Tests

```bash
make test
```

#### Run Specific Test Suites

```bash
# Unit tests only
make test-unit

# Integration tests only
make test-integration

# Functional tests only
make test-functional

# Smoke tests only (quick check)
make test-smoke
```

#### Run Tests with Coverage

```bash
make test-coverage
```

Coverage report will be generated at `var/coverage/index.html`

#### Run Tests with Verbose Output

```bash
make test-verbose
```

### Using Shell Scripts

#### Run All Tests

```bash
./bin/run-tests.sh all
```

#### Run Specific Test Types

```bash
# Unit tests
./bin/run-tests.sh unit

# Integration tests
./bin/run-tests.sh integration

# Functional tests
./bin/run-tests.sh functional

# Smoke tests
./bin/run-tests.sh smoke

# Coverage report
./bin/run-tests.sh coverage
```

### Using PHPUnit Directly

#### Run All Tests

```bash
./bin/phpunit
```

#### Run Tests by Suite

```bash
# Unit tests
./bin/phpunit --testsuite="Unit Tests"

# Integration tests
./bin/phpunit --testsuite="Integration Tests"

# Functional tests
./bin/phpunit --testsuite="Functional Tests"
```

#### Run Tests by Group

```bash
# Smoke tests
./bin/phpunit --group=smoke

# Entity tests
./bin/phpunit --group=entity

# Repository tests
./bin/phpunit --group=repository

# Controller tests
./bin/phpunit --group=controller
```

#### Run Specific Test File

```bash
./bin/phpunit tests/Unit/Entity/OrderTest.php
```

#### Run Specific Test Method

```bash
./bin/phpunit --filter testOrderCreationWithDefaultValues
```

### Using Docker

If you prefer to run tests inside Docker:

```bash
docker container exec -it xmedia-app ./bin/phpunit
```

## Static Code Analysis

Static analysis helps catch bugs and maintain code quality without running the code.

### Tools Used

1. **PHPStan** - Static analysis tool that finds bugs
2. **PHP CodeSniffer** - Code style checker (PSR-12 standard)

### Using Makefile (Recommended)

#### Run All Static Analysis

```bash
make phpstan
make phpcs
```

Or run both at once:

```bash
make qa
```

#### Run Static Analysis on Changed Files Only

```bash
make phpstan-changed
```

#### Auto-fix Code Style Issues

```bash
make phpcbf
```

### Using Shell Scripts

#### Analyze All Code

```bash
./bin/static-analysis.sh all
```

#### Analyze Changed Files Only

```bash
./bin/static-analysis.sh changed
```

This will only analyze PHP files that have been modified but not yet committed.

#### Run Specific Tool

```bash
# PHPStan only
./bin/static-analysis.sh phpstan

# PHP CodeSniffer only
./bin/static-analysis.sh phpcs

# Auto-fix code style
./bin/static-analysis.sh fix
```

### Using Tools Directly

#### PHPStan

```bash
# Analyze all code
vendor/bin/phpstan analyse src --memory-limit=2G

# Analyze specific directory
vendor/bin/phpstan analyse src/Entity --memory-limit=2G

# Analyze specific file
vendor/bin/phpstan analyse src/Entity/Order.php --memory-limit=2G
```

#### PHP CodeSniffer

```bash
# Check all code
vendor/bin/phpcs --standard=PSR12 src

# Check specific directory
vendor/bin/phpcs --standard=PSR12 src/Controller

# Check specific file
vendor/bin/phpcs --standard=PSR12 src/Entity/Order.php

# Auto-fix issues
vendor/bin/phpcbf --standard=PSR12 src
```

### Static Analysis for Changed Files Only

This is useful for quick checks before committing:

```bash
# Get changed files and analyze them
git diff --name-only --diff-filter=AM | grep '\.php$' | xargs vendor/bin/phpstan analyse --memory-limit=2G
git diff --name-only --diff-filter=AM | grep '\.php$' | xargs vendor/bin/phpcs --standard=PSR12
```

Or use the provided script:

```bash
./bin/static-analysis.sh changed
```

## Pre-commit Hooks

Pre-commit hooks automatically run checks before allowing a commit, ensuring code quality.

### Setup Pre-commit Hooks

Run the setup script once:

```bash
./bin/setup-git-hooks.sh
```

This will install a pre-commit hook that runs:

1. **PHPStan** on changed files
2. **PHP CodeSniffer** on changed files
3. **Smoke tests** to verify critical functionality

### What Happens on Commit

When you run `git commit`, the pre-commit hook will:

1. Identify changed PHP files
2. Run PHPStan on those files
3. Run PHP CodeSniffer on those files
4. Run smoke tests
5. Allow commit only if all checks pass

**Example output:**

```
Running pre-commit checks...

Changed PHP files:
src/Entity/Order.php
src/Controller/OrderController.php

Running PHPStan (changed files)...
✓ PHPStan (changed files) passed

Running PHP CodeSniffer (changed files)...
✓ PHP CodeSniffer (changed files) passed

Running Smoke Tests...
✓ Smoke Tests passed

All pre-commit checks passed! ✓
```

### Bypassing Pre-commit Hooks

If you need to commit without running hooks (not recommended):

```bash
git commit --no-verify -m "Your commit message"
```

### Fixing Issues Before Commit

If pre-commit checks fail:

```bash
# Auto-fix code style issues
make phpcbf

# Or manually fix PHPStan issues and re-commit
```

## CI/CD Integration

### Running Tests in CI/CD Pipeline

#### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          
      - name: Install Dependencies
        run: composer install --prefer-dist --no-progress
        
      - name: Run Static Analysis
        run: |
          vendor/bin/phpstan analyse src --memory-limit=2G
          vendor/bin/phpcs --standard=PSR12 src
          
      - name: Run Tests
        run: ./bin/phpunit
```

#### GitLab CI Example

```yaml
test:
  stage: test
  image: php:8.4
  script:
    - composer install
    - vendor/bin/phpstan analyse src --memory-limit=2G
    - vendor/bin/phpcs --standard=PSR12 src
    - ./bin/phpunit
```

### Docker-based CI/CD

```bash
# Run in Docker
docker container exec xmedia-app vendor/bin/phpstan analyse src --memory-limit=2G
docker container exec xmedia-app vendor/bin/phpcs --standard=PSR12 src
docker container exec xmedia-app ./bin/phpunit
```

## Writing Tests

### Test Naming Conventions

- Test class names should end with `Test` (e.g., `OrderTest`)
- Test methods should start with `test` (e.g., `testOrderCreation`)
- Use descriptive names that explain what is being tested

### Test Structure (AAA Pattern)

```php
public function testSomething(): void
{
    // Arrange - Set up test data
    $order = new Order();
    $order->setName('John');
    
    // Act - Execute the code being tested
    $name = $order->getName();
    
    // Assert - Verify the results
    $this->assertSame('John', $name);
}
```

### Adding Test Groups

Use PHPDoc annotations to add tests to groups:

```php
/**
 * @group unit
 * @group entity
 * @group smoke
 */
class OrderTest extends TestCase
{
    // ...
}
```

### Best Practices

1. **Keep tests independent** - Tests should not depend on each other
2. **Test one thing at a time** - Each test should verify one behavior
3. **Use descriptive names** - Test names should explain what they test
4. **Clean up after tests** - Remove test data in Integration/Functional tests
5. **Mock external dependencies** - Use mocks for external services in unit tests
6. **Test edge cases** - Don't just test happy paths
7. **Keep tests fast** - Unit tests should run in milliseconds

### Writing Unit Tests

```php
namespace App\Tests\Unit\Entity;

use App\Entity\Product;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group entity
 */
class ProductTest extends TestCase
{
    public function testProductPrice(): void
    {
        $product = new Product();
        $product->setPrice(1500);
        
        $this->assertSame(1500, $product->getPrice());
    }
}
```

### Writing Integration Tests

```php
namespace App\Tests\Integration\Repository;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group integration
 * @group repository
 */
class ProductRepositoryTest extends KernelTestCase
{
    private ProductRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()
            ->get(ProductRepository::class);
    }

    public function testFindActiveProducts(): void
    {
        $products = $this->repository->findBy([
            'status' => Product::STATUS_ACTIVE
        ]);
        
        $this->assertGreaterThan(0, count($products));
    }
}
```

### Writing Functional Tests

```php
namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group functional
 * @group smoke
 */
class HomePageControllerTest extends WebTestCase
{
    public function testHomePageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');
        
        $this->assertResponseIsSuccessful();
    }
}
```

## Quick Reference

### Common Commands

```bash
# Run all tests
make test

# Run smoke tests (quick check)
make test-smoke

# Run static analysis
make phpstan
make phpcs

# Run everything (QA)
make qa

# Auto-fix code style
make phpcbf

# Setup git hooks
./bin/setup-git-hooks.sh

# Analyze changed files only
./bin/static-analysis.sh changed
```

### Test Count Summary

- **Unit Tests**: 3 files, 20+ test methods
- **Integration Tests**: 2 files, 8 test methods
- **Functional Tests**: 4 files, 11 test methods
- **Smoke Tests**: 6 test methods

### Coverage Goals

- **Unit Tests**: 80%+ coverage
- **Integration Tests**: Critical repository methods
- **Functional Tests**: All public endpoints
- **Smoke Tests**: Core user flows

## Troubleshooting

### Tests Failing

1. Ensure database is set up correctly
2. Run migrations: `php bin/console doctrine:migrations:migrate`
3. Clear cache: `php bin/console cache:clear --env=test`

### PHPStan Errors

1. Check PHPStan configuration: `phpstan.neon`
2. Increase memory limit if needed
3. Check for missing PHPDoc annotations

### PHP CodeSniffer Errors

1. Auto-fix when possible: `make phpcbf`
2. Manually fix remaining issues
3. Check PSR-12 coding standards

## Support

For questions or issues with testing:

1. Check this documentation
2. Review test examples in `tests/` directory
3. Consult PHPUnit documentation: https://phpunit.de/
4. Consult PHPStan documentation: https://phpstan.org/

---

**Last Updated**: November 2025
**Version**: 1.0.0

