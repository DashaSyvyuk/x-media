# Quick Start Guide - Testing & Automation

## 🚀 Get Started in 3 Steps

### 1. Setup Git Hooks (One-time setup)

```bash
./bin/setup-git-hooks.sh
```

This installs pre-commit hooks that automatically check your code before each commit.

### 2. Run Tests

```bash
# Run all tests
make test

# Run smoke tests (quick check - recommended during development)
make test-smoke
```

### 3. Check Code Quality

```bash
# Run static analysis
make phpstan

# Check code style
make phpcs

# Auto-fix code style issues
make phpcbf
```

## 📋 Common Commands

### Testing

| Command | What it does |
|---------|-------------|
| `make test` | Run all tests |
| `make test-smoke` | Run smoke tests (fast) |
| `make test-unit` | Run unit tests only |
| `make test-integration` | Run integration tests only |
| `make test-functional` | Run functional tests only |
| `make test-coverage` | Generate HTML coverage report |

### Code Quality

| Command | What it does |
|---------|-------------|
| `make phpstan` | Run static analysis on all code |
| `make phpstan-changed` | Run static analysis on changed files only |
| `make phpcs` | Check code style (PSR-12) |
| `make phpcbf` | Auto-fix code style issues |
| `make qa` | Run all quality checks |
| `make qa-quick` | Run quick quality checks |

### Alternative: Using Scripts

```bash
# Tests
./bin/run-tests.sh all          # All tests
./bin/run-tests.sh smoke        # Smoke tests
./bin/run-tests.sh unit         # Unit tests
./bin/run-tests.sh integration  # Integration tests
./bin/run-tests.sh functional   # Functional tests
./bin/run-tests.sh coverage     # Coverage report

# Static Analysis
./bin/static-analysis.sh all     # All code
./bin/static-analysis.sh changed # Changed files only
./bin/static-analysis.sh fix     # Auto-fix style issues
```

## 🔄 Typical Workflow

### During Development

1. Write code
2. Run smoke tests: `make test-smoke` (fast feedback)
3. Fix any issues
4. Commit (pre-commit hooks will run automatically)

### Before Creating PR

1. Run all tests: `make test`
2. Run static analysis: `make phpstan`
3. Check code style: `make phpcs`
4. Generate coverage: `make test-coverage`
5. Push and create PR

### Quick Check

```bash
make qa-quick
```

This runs:
- Static analysis on changed files only
- Smoke tests

Perfect for a quick check before committing!

## 📚 Documentation

Need more details? Check these docs:

- **[TESTING.md](TESTING.md)** - Complete testing guide
- **[PRE_COMMIT_HOOKS.md](PRE_COMMIT_HOOKS.md)** - Pre-commit hooks setup
- **[AUTOMATION_SUMMARY.md](AUTOMATION_SUMMARY.md)** - Everything we created
- **[README.md](README.md)** - Project overview

## ❓ Common Questions

### Q: Tests are failing after I made changes

**A:** Run the specific test suite to see details:
```bash
make test-unit    # If unit tests failing
make test-verbose # See detailed output
```

### Q: Pre-commit hook is blocking my commit

**A:** Fix the issues:
```bash
make phpcbf  # Auto-fix code style
```

Then review PHPStan errors and fix manually.

### Q: I want to commit without running hooks

**A:** Not recommended, but possible:
```bash
git commit --no-verify -m "Your message"
```

### Q: How do I run just one test file?

**A:**
```bash
./bin/phpunit tests/Unit/Entity/OrderTest.php
```

### Q: How do I run a specific test method?

**A:**
```bash
./bin/phpunit --filter testOrderCreationWithDefaultValues
```

### Q: Tests are too slow

**A:** Run smoke tests during development:
```bash
make test-smoke
```

Save full test suite for before committing/pushing.

### Q: I want to see code coverage

**A:**
```bash
make test-coverage
```

Then open `var/coverage/index.html` in your browser.

## 🎯 What Gets Tested?

✅ **Entities** - Order, Product (creation, getters/setters, relationships)  
✅ **Utilities** - OrderNumber generation and uniqueness  
✅ **Repositories** - CRUD operations, queries  
✅ **Controllers** - HTTP responses, status codes  
✅ **Static Pages** - Homepage, product pages, category pages  

## 🛠️ Troubleshooting

### Docker Container Not Running

```bash
docker-compose up -d
```

### Database Issues

```bash
docker container exec -it xmedia-app php bin/console doctrine:migrations:migrate
```

### Cache Issues

```bash
docker container exec -it xmedia-app php bin/console cache:clear --env=test
```

### Permission Issues

```bash
chmod +x ./bin/run-tests.sh
chmod +x ./bin/static-analysis.sh
chmod +x ./bin/setup-git-hooks.sh
```

## 💡 Pro Tips

1. **Use smoke tests during development** - They're fast (< 30 sec)
2. **Run full tests before pushing** - Catch issues early
3. **Check coverage regularly** - Aim for 80%+ on critical code
4. **Let pre-commit hooks work** - They save you from CI failures
5. **Use `make qa-quick`** - Perfect balance of speed and coverage

## 🎉 You're Ready!

Your development environment is now fully equipped with:

- ✅ Automated testing (45+ tests)
- ✅ Static analysis (PHPStan + PHPCS)
- ✅ Pre-commit hooks (quality gates)
- ✅ One-command execution (Makefile)
- ✅ Comprehensive documentation

Start coding with confidence! 🚀

---

**Questions?** Check [TESTING.md](TESTING.md) for detailed information.

