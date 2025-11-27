#!/bin/bash

# Setup script for git hooks

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo -e "${GREEN}X-Media Git Hooks Setup${NC}"
echo "================================"

# Check if .git directory exists
if [ ! -d "$PROJECT_ROOT/.git" ]; then
    echo -e "${RED}Error: .git directory not found. Are you in a git repository?${NC}"
    exit 1
fi

# Create symbolic link to pre-commit hook
HOOKS_SOURCE="$PROJECT_ROOT/.git-hooks/pre-commit"
HOOKS_TARGET="$PROJECT_ROOT/.git/hooks/pre-commit"

if [ -f "$HOOKS_TARGET" ] || [ -L "$HOOKS_TARGET" ]; then
    echo -e "${YELLOW}Pre-commit hook already exists. Backing up...${NC}"
    mv "$HOOKS_TARGET" "$HOOKS_TARGET.backup"
fi

# Create the symbolic link
ln -s "$HOOKS_SOURCE" "$HOOKS_TARGET"

# Make the hook executable
chmod +x "$HOOKS_SOURCE"
chmod +x "$HOOKS_TARGET"

echo -e "${GREEN}✓ Pre-commit hook installed successfully!${NC}"
echo ""
echo "The pre-commit hook will now run automatically before each commit."
echo "It will check:"
echo "  - PHPStan static analysis on changed files"
echo "  - PHP CodeSniffer code style on changed files"
echo ""
echo -e "${YELLOW}To skip the hook (not recommended), use: git commit --no-verify${NC}"
echo ""
echo -e "${GREEN}Setup complete!${NC}"

