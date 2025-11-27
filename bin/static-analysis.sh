#!/bin/bash

# Static Analysis Scripts for X-Media Project

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo -e "${GREEN}X-Media Static Analysis Runner${NC}"
echo "================================"

# Function to run static analysis
run_analysis() {
    local analysis_type=$1
    local analysis_command=$2
    
    echo -e "\n${YELLOW}Running $analysis_type...${NC}"
    cd "$PROJECT_ROOT" || exit 1
    
    if eval "$analysis_command"; then
        echo -e "${GREEN}✓ $analysis_type passed${NC}"
        return 0
    else
        echo -e "${RED}✗ $analysis_type failed${NC}"
        return 1
    fi
}

# Function to get changed PHP files
get_changed_files() {
    git diff --name-only --diff-filter=AM HEAD | grep '\.php$'
}

# Main execution
case "${1:-all}" in
    all)
        echo "Running static analysis on all code..."
        run_analysis "PHPStan" "vendor/bin/phpstan analyse src --memory-limit=2G"
        phpstan_result=$?
        
        run_analysis "PHP CodeSniffer" "vendor/bin/phpcs --standard=PSR12 src"
        phpcs_result=$?
        
        if [ $phpstan_result -eq 0 ] && [ $phpcs_result -eq 0 ]; then
            echo -e "\n${GREEN}All static analysis checks passed!${NC}"
            exit 0
        else
            echo -e "\n${RED}Some static analysis checks failed!${NC}"
            exit 1
        fi
        ;;
    changed)
        echo "Running static analysis on changed files only..."
        changed_files=$(get_changed_files)
        
        if [ -z "$changed_files" ]; then
            echo -e "${YELLOW}No changed PHP files found.${NC}"
            exit 0
        fi
        
        echo -e "${YELLOW}Changed files:${NC}"
        echo "$changed_files"
        echo ""
        
        # Run PHPStan on changed files
        echo -e "${YELLOW}Running PHPStan on changed files...${NC}"
        echo "$changed_files" | xargs vendor/bin/phpstan analyse --memory-limit=2G
        phpstan_result=$?
        
        # Run PHPCS on changed files
        echo -e "${YELLOW}Running PHP CodeSniffer on changed files...${NC}"
        echo "$changed_files" | xargs vendor/bin/phpcs --standard=PSR12
        phpcs_result=$?
        
        if [ $phpstan_result -eq 0 ] && [ $phpcs_result -eq 0 ]; then
            echo -e "\n${GREEN}All static analysis checks passed on changed files!${NC}"
            exit 0
        else
            echo -e "\n${RED}Some static analysis checks failed on changed files!${NC}"
            exit 1
        fi
        ;;
    phpstan)
        run_analysis "PHPStan" "vendor/bin/phpstan analyse src --memory-limit=2G"
        ;;
    phpcs)
        run_analysis "PHP CodeSniffer" "vendor/bin/phpcs --standard=PSR12 src"
        ;;
    fix)
        echo "Auto-fixing code style issues..."
        vendor/bin/phpcbf --standard=PSR12 src
        echo -e "${GREEN}Code style fixes applied!${NC}"
        ;;
    *)
        echo "Usage: $0 {all|changed|phpstan|phpcs|fix}"
        echo ""
        echo "Commands:"
        echo "  all     - Run all static analysis on entire codebase"
        echo "  changed - Run static analysis only on changed files"
        echo "  phpstan - Run only PHPStan on entire codebase"
        echo "  phpcs   - Run only PHP CodeSniffer on entire codebase"
        echo "  fix     - Auto-fix code style issues"
        exit 1
        ;;
esac

exit $?

