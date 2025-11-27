#!/bin/bash

# Test Execution Scripts for X-Media Project

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo -e "${GREEN}X-Media Test Runner${NC}"
echo "================================"

# Function to run tests
run_tests() {
    local test_type=$1
    local test_command=$2
    
    echo -e "\n${YELLOW}Running $test_type...${NC}"
    cd "$PROJECT_ROOT" || exit 1
    
    if $test_command; then
        echo -e "${GREEN}✓ $test_type passed${NC}"
        return 0
    else
        echo -e "${RED}✗ $test_type failed${NC}"
        return 1
    fi
}

# Main execution
case "${1:-all}" in
    all)
        echo "Running all tests..."
        run_tests "All Tests" "./bin/phpunit"
        ;;
    unit)
        run_tests "Unit Tests" './bin/phpunit --testsuite="Unit Tests"'
        ;;
    integration)
        run_tests "Integration Tests" './bin/phpunit --testsuite="Integration Tests"'
        ;;
    functional)
        run_tests "Functional Tests" './bin/phpunit --testsuite="Functional Tests"'
        ;;
    smoke)
        run_tests "Smoke Tests" './bin/phpunit --group=smoke'
        ;;
    coverage)
        echo "Generating code coverage report..."
        ./bin/phpunit --coverage-html var/coverage
        echo -e "${GREEN}Coverage report generated at var/coverage/index.html${NC}"
        ;;
    *)
        echo "Usage: $0 {all|unit|integration|functional|smoke|coverage}"
        exit 1
        ;;
esac

exit $?

