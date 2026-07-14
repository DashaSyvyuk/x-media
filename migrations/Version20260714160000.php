<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250714160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Vendor order line items for multiple products per supplier order';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE vendor_order_items (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            vendor_order_id INT UNSIGNED NOT NULL,
            title LONGTEXT NOT NULL,
            price INT NOT NULL,
            quantity INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_VENDOR_ORDER_ITEMS_ORDER (vendor_order_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE vendor_order_items ADD CONSTRAINT FK_VENDOR_ORDER_ITEMS_ORDER FOREIGN KEY (vendor_order_id) REFERENCES vendor_orders (id) ON DELETE CASCADE');

        $this->addSql('INSERT INTO vendor_order_items (vendor_order_id, title, price, quantity, created_at, updated_at)
            SELECT id, product_title, price, 1, created_at, updated_at FROM vendor_orders');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vendor_order_items DROP FOREIGN KEY FK_VENDOR_ORDER_ITEMS_ORDER');
        $this->addSql('DROP TABLE vendor_order_items');
    }
}
