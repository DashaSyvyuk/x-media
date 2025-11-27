# Pre-commit Hooks Setup Instructions

This document provides instructions for setting up and using pre-commit hooks in the X-Media project.

## What are Pre-commit Hooks?

Pre-commit hooks are automated scripts that run before each git commit. They help maintain code quality by:

- Running static analysis on your changes
- Checking code style compliance
- Running quick tests (smoke tests)
- Preventing commits that don't meet quality standards

## Quick Setup

Run the setup script:

```bash
./bin/setup-git-hooks.sh
```

That's it! The pre-commit hook is now installed and will run automatically.

## What the Pre-commit Hook Does

When you commit code, the hook will:

1. **Identify Changed PHP Files**: Only checks files you've modified
2. **Run PHPStan**: Static analysis to find potential bugs
3. **Run PHP CodeSniffer**: Checks code style (PSR-12 standard)
4. **Run Smoke Tests**: Quick tests to verify critical functionality

## Example Workflow

### Successful Commit

```bash
$ git add src/Entity/Order.php
$ git commit -m "Update Order entity"

Running pre-commit checks...

Changed PHP files:
src/Entity/Order.php

Running PHPStan (changed files)...
✓ PHPStan (changed files) passed

Running PHP CodeSniffer (changed files)...
✓ PHP CodeSniffer (changed files) passed

Running Smoke Tests...
✓ Smoke Tests passed

All pre-commit checks passed! ✓
[master abc1234] Update Order entity
 1 file changed, 10 insertions(+), 2 deletions(-)
```

### Failed Commit

```bash
$ git add src/Entity/Order.php
$ git commit -m "Update Order entity"

Running pre-commit checks...

Changed PHP files:
src/Entity/Order.php

Running PHPStan (changed files)...
✗ PHPStan (changed files) failed

Pre-commit checks failed! Commit aborted.
To fix code style issues, run: make phpcbf
To skip this hook (not recommended), use: git commit --no-verify
```

## Fixing Issues

### Code Style Issues

Auto-fix code style problems:

```bash
make phpcbf
```

Or manually:

```bash
vendor/bin/phpcbf --standard=PSR12 src
```

### PHPStan Issues

PHPStan issues usually require manual fixes:

1. Read the error message
2. Fix the issue in your code
3. Re-run the check: `make phpstan`
4. Try committing again

### Test Failures

If smoke tests fail:

1. Run the full test suite: `make test`
2. Fix failing tests
3. Try committing again

## Bypassing the Hook

**Not recommended**, but if you must bypass the hook:

```bash
git commit --no-verify -m "Your commit message"
```

Only do this for:
- Emergency fixes
- Work-in-progress commits on feature branches
- When the hook is incorrectly blocking valid code

## Uninstalling the Hook

To remove the pre-commit hook:

```bash
rm .git/hooks/pre-commit
```

## Manual Installation

If the setup script doesn't work, install manually:

```bash
# Create symbolic link
ln -s ../../.git-hooks/pre-commit .git/hooks/pre-commit

# Make executable
chmod +x .git-hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

## Customizing the Hook

The pre-commit hook is located at `.git-hooks/pre-commit`. You can edit it to:

- Add or remove checks
- Change which tests run
- Adjust failure behavior
- Add custom validation

After editing, re-run the setup script to apply changes.

## Troubleshooting

### Hook Not Running

1. Check if hook is installed: `ls -la .git/hooks/pre-commit`
2. Check if hook is executable: `ls -l .git/hooks/pre-commit`
3. Re-run setup: `./bin/setup-git-hooks.sh`

### Hook Runs But Fails Every Time

1. Test the hook manually:
   ```bash
   .git/hooks/pre-commit
   ```
2. Check if required tools are installed:
   ```bash
   vendor/bin/phpstan --version
   vendor/bin/phpcs --version
   ./bin/phpunit --version
   ```

### Hook is Too Slow

If the hook takes too long:

1. Edit `.git-hooks/pre-commit`
2. Remove or comment out slow checks
3. Or use `git commit --no-verify` for WIP commits

## Best Practices

1. **Run checks before committing**: Test your changes locally first
   ```bash
   make qa-quick  # Quick quality checks
   ```

2. **Fix issues immediately**: Don't let code quality issues accumulate

3. **Don't bypass hooks regularly**: They're there to help you

4. **Keep hooks fast**: Only run essential checks in pre-commit

5. **Run full test suite separately**: Use CI/CD for comprehensive testing

## Integration with Team Workflow

### For New Team Members

Add to onboarding:
```bash
git clone <repository>
cd x-media
composer install
./bin/setup-git-hooks.sh
```

### For Existing Repositories

Remind team to run:
```bash
./bin/setup-git-hooks.sh
```

### For CI/CD

Pre-commit hooks complement CI/CD:

- **Pre-commit**: Fast checks on local changes
- **CI/CD**: Comprehensive testing on all code

## Additional Resources

- [Git Hooks Documentation](https://git-scm.com/book/en/v2/Customizing-Git-Git-Hooks)
- [PHPStan Documentation](https://phpstan.org/)
- [PHP CodeSniffer Documentation](https://github.com/squizlabs/PHP_CodeSniffer)
- [PHPUnit Documentation](https://phpunit.de/)

## Questions?

See the main [TESTING.md](TESTING.md) documentation for more information about:
- Running tests manually
- Static code analysis
- Writing tests
- Test types and coverage

---

**Last Updated**: November 2025

