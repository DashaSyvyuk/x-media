# X-Media E-commerce Platform

Online shop platform built with Symfony framework.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Testing](#testing)
- [Code Quality](#code-quality)
- [Development](#development)

## Features

- Product catalog management
- Order processing
- User authentication
- Category management
- Shopping cart
- Multiple delivery options (Nova Poshta integration)
- Admin panel
- Product reviews and ratings

## Requirements

- PHP 8.4+
- MySQL/MariaDB
- Composer
- Docker & Docker Compose
- Node.js & NPM (for assets)

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd x-media
```

2. Install dependencies:
```bash
composer install
npm install
```

3. Start Docker containers:
```bash
docker-compose up -d
```

4. Run database migrations:
```bash
docker container exec -it xmedia-app php bin/console doctrine:migrations:migrate
```

5. Build assets:
```bash
npm run build
```

## Testing

The project includes comprehensive test coverage with multiple test types.

### Quick Start

Run all tests:
```bash
make test
```

Run smoke tests (quick check):
```bash
make test-smoke
```

### Test Types

- **Unit Tests**: Test individual components in isolation
- **Integration Tests**: Test database operations and repositories
- **Functional Tests**: Test HTTP endpoints and controllers
- **Smoke Tests**: Quick tests for critical functionality

### Running Specific Test Suites

```bash
# Unit tests only
make test-unit

# Integration tests only
make test-integration

# Functional tests only
make test-functional

# Smoke tests only
make test-smoke
```

### Test Coverage

Generate HTML coverage report:
```bash
make test-coverage
```

View the report at `var/coverage/index.html`

### Detailed Testing Documentation

For comprehensive testing instructions, see [TESTING.md](TESTING.md)

## Code Quality

### Static Analysis

Run PHPStan static analysis:
```bash
make phpstan
```

Run PHP CodeSniffer (code style check):
```bash
make phpcs
```

Run on changed files only:
```bash
make phpstan-changed
```

### Auto-fix Code Style

```bash
make phpcbf
```

### Run All Quality Checks

```bash
make qa
```

### Static Analysis Scripts

Use the provided scripts for more options:

```bash
# Analyze all code
./bin/static-analysis.sh all

# Analyze changed files only (fast)
./bin/static-analysis.sh changed

# Run specific tools
./bin/static-analysis.sh phpstan
./bin/static-analysis.sh phpcs

# Auto-fix code style
./bin/static-analysis.sh fix
```

## Pre-commit Hooks

Set up automatic code quality checks before each commit:

```bash
./bin/setup-git-hooks.sh
```

This will run before each commit:
- PHPStan on changed files
- PHP CodeSniffer on changed files  
- Smoke tests

For more details, see [PRE_COMMIT_HOOKS.md](PRE_COMMIT_HOOKS.md)

## Development

### Available Make Commands

```bash
# Testing
make test              # Run all tests
make test-unit         # Run unit tests
make test-integration  # Run integration tests
make test-functional   # Run functional tests
make test-smoke        # Run smoke tests
make test-coverage     # Generate coverage report

# Code Quality
make phpstan           # Run PHPStan
make phpstan-changed   # Run PHPStan on changed files
make phpcs             # Run PHP CodeSniffer
make phpcbf            # Auto-fix code style

# Combined
make qa                # Run all quality checks
make qa-quick          # Run quick quality checks
```

### Project Structure

```
x-media/
├── src/
│   ├── Controller/     # HTTP controllers
│   ├── Entity/         # Doctrine entities
│   ├── Repository/     # Database repositories
│   ├── Service/        # Business logic services
│   ├── Form/           # Form types
│   └── Utils/          # Utility classes
├── tests/
│   ├── Unit/           # Unit tests
│   ├── Integration/    # Integration tests
│   └── Functional/     # Functional tests
├── templates/          # Twig templates
├── public/             # Public web directory
└── config/             # Configuration files
```

## Documentation

- [Testing Guide](TESTING.md) - Comprehensive testing documentation
- [Pre-commit Hooks](PRE_COMMIT_HOOKS.md) - Git hooks setup and usage

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests and static analysis
5. Commit your changes (pre-commit hooks will run automatically)
6. Push to your fork
7. Create a pull request

## License

Proprietary

---

For questions or support, please contact the development team.
