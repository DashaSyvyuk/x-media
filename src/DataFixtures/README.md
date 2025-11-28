# Test Fixtures

This directory contains Symfony Data Fixtures for testing purposes.

## Fixtures Overview

### TestFixtures.php

This fixture loads test data for functional tests including:

**Settings:**
- Phone number: +380123456789
- Email: test@example.com  
- Shop name, address, and other configuration
- Pagination limit: 20

**Categories:**
1. Ноутбуки (Notebooks) - slug: `notebooks`
2. Смартфони (Smartphones) - slug: `smartphones`
3. Планшети (Tablets) - slug: `tablets`

**Products:**
7 test products across different categories including:
- Apple MacBook Pro 16 (MBP-16-001) - 89,999 грн
- iPhone 15 Pro (IPH-15P-004) - 42,999 грн
- Samsung Galaxy S24 (SAM-S24-005) - 35,999 грн
- iPad Pro 12.9 (IPAD-PRO-006) - 48,999 грн
- And more...

## Loading Fixtures

### Test Environment

To load fixtures into the test database:

```bash
# Using Docker
docker container exec xmedia-app php bin/console doctrine:fixtures:load --env=test --no-interaction

# Or using Makefile (if available)
make fixtures-test
```

### Development Environment

```bash
php bin/console doctrine:fixtures:load
```

**⚠️ Warning:** Loading fixtures will purge all existing data in the database!

## Using Fixtures in Tests

The fixtures are automatically available when you run tests against the test database.

### Example Test

```php
public function testProductSearch(): void
{
    $client = static::createClient();
    
    // Search for products from fixtures
    $client->request('GET', '/search?search=iPhone');
    
    $this->assertResponseIsSuccessful();
}
```

## Adding New Fixtures

To add new test data:

1. Edit `src/DataFixtures/TestFixtures.php`
2. Add your entities in the `load()` method
3. Reload the fixtures

### Example

```php
// In TestFixtures::load()
$newProduct = new Product();
$newProduct->setTitle('New Test Product');
$newProduct->setProductCode('NEW-001');
$newProduct->setPrice(19999);
// ... set other required fields
$manager->persist($newProduct);

$manager->flush();
```

## Fixture Dependencies

The current fixtures create data in this order:
1. Settings (required by base controllers)
2. Categories (required by products)
3. Products

Maintain this order when adding new fixtures to avoid foreign key constraint violations.

## Notes

- Fixtures use Ukrainian language for product names and descriptions
- All products are set to `STATUS_ACTIVE` and `AVAILABILITY_AVAILABLE`
- Product codes follow the pattern: `PREFIX-CODE-###`
- Prices are in kopiykas (Ukrainian currency subunit)

