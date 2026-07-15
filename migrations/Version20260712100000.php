<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260712100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Vendor orders, fulfillment links, supplier storage days; drop rozetka_order_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suppliers ADD order_storage_days INT UNSIGNED DEFAULT NULL');

        $this->addSql('CREATE TABLE vendor_orders (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            supplier_id INT UNSIGNED NOT NULL,
            supplier_order_number VARCHAR(255) NOT NULL,
            product_title LONGTEXT NOT NULL,
            price INT NOT NULL,
            notes LONGTEXT DEFAULT NULL,
            status VARCHAR(32) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_VENDOR_ORDERS_SUPPLIER (supplier_id),
            INDEX IDX_VENDOR_ORDERS_STATUS (status),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE vendor_orders ADD CONSTRAINT FK_VENDOR_ORDERS_SUPPLIER FOREIGN KEY (supplier_id) REFERENCES suppliers (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE order_fulfillment_links (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            link_group VARCHAR(16) NOT NULL,
            vendor_order_id INT UNSIGNED NOT NULL,
            order_id INT UNSIGNED DEFAULT NULL,
            rozetka_order_id INT UNSIGNED DEFAULT NULL,
            INDEX IDX_FULFILLMENT_LINK_GROUP (link_group),
            INDEX IDX_FULFILLMENT_VENDOR (vendor_order_id),
            INDEX IDX_FULFILLMENT_ORDER (order_id),
            INDEX IDX_FULFILLMENT_ROZETKA (rozetka_order_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE order_fulfillment_links ADD CONSTRAINT FK_FULFILLMENT_VENDOR FOREIGN KEY (vendor_order_id) REFERENCES vendor_orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_fulfillment_links ADD CONSTRAINT FK_FULFILLMENT_ORDER FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');

        $this->addSql('DROP INDEX UNIQ_E52FFDEEA7AEB58 ON orders');
        $this->addSql('ALTER TABLE orders DROP rozetka_order_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders ADD rozetka_order_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E52FFDEEA7AEB58 ON orders (rozetka_order_id)');

        $this->addSql('ALTER TABLE order_fulfillment_links DROP FOREIGN KEY FK_FULFILLMENT_VENDOR');
        $this->addSql('ALTER TABLE order_fulfillment_links DROP FOREIGN KEY FK_FULFILLMENT_ORDER');
        $this->addSql('DROP TABLE order_fulfillment_links');

        $this->addSql('ALTER TABLE vendor_orders DROP FOREIGN KEY FK_VENDOR_ORDERS_SUPPLIER');
        $this->addSql('DROP TABLE vendor_orders');

        $this->addSql('ALTER TABLE suppliers DROP order_storage_days');
    }
}
