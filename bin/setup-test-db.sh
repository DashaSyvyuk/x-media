#!/bin/bash

# Test Database Setup Script
# Run this once to prepare the test database for integration and functional tests

set -e  # Exit on error

echo "========================================="
echo "X-Media Test Database Setup"
echo "========================================="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Check if Docker is running
if ! docker ps &> /dev/null; then
    echo -e "${RED}Error: Docker is not running${NC}"
    echo "Please start Docker and try again."
    exit 1
fi

# Check if xmedia-app container exists
if ! docker ps -a | grep -q xmedia-app; then
    echo -e "${RED}Error: xmedia-app container not found${NC}"
    echo "Please run: docker-compose up -d"
    exit 1
fi

# Step 1: Drop existing test database (if any)
echo -e "${YELLOW}Step 1: Dropping existing test database (if exists)...${NC}"
docker container exec xmedia-app php bin/console doctrine:database:drop --force --env=test 2>/dev/null || echo "No existing database to drop"
echo ""

# Step 2: Create test database
echo -e "${YELLOW}Step 2: Creating test database...${NC}"
if docker container exec xmedia-app php bin/console doctrine:database:create --env=test; then
    echo -e "${GREEN}✓ Test database created${NC}"
else
    echo -e "${RED}✗ Failed to create test database${NC}"
    exit 1
fi
echo ""

# Step 3: Run migrations
echo -e "${YELLOW}Step 3: Running migrations...${NC}"
if docker container exec xmedia-app php bin/console doctrine:migrations:migrate --no-interaction --env=test; then
    echo -e "${GREEN}✓ Migrations completed${NC}"
else
    echo -e "${RED}✗ Failed to run migrations${NC}"
    exit 1
fi
echo ""

# Step 4: Verify setup
echo -e "${YELLOW}Step 4: Verifying setup...${NC}"
if docker container exec xmedia-app php bin/console dbal:run-sql "SELECT 1" --env=test &> /dev/null; then
    echo -e "${GREEN}✓ Database connection verified${NC}"
else
    echo -e "${RED}✗ Database connection failed${NC}"
    exit 1
fi
echo ""

echo "========================================="
echo -e "${GREEN}✓ Test database setup complete!${NC}"
echo "========================================="
echo ""
echo "You can now run:"
echo "  make test-integration  # Integration tests"
echo "  make test-functional   # Functional tests"
echo "  make test             # All tests"
echo ""
echo "To reset the test database later, run this script again."

