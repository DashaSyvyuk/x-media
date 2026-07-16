<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add planning goods (Товар) batches and units for admin2';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE planning_good_batches (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            recorded_date DATE NOT NULL,
            name VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE planning_goods (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            planning_good_batch_id INT UNSIGNED NOT NULL,
            warehouse_id INT UNSIGNED DEFAULT NULL,
            name VARCHAR(255) NOT NULL,
            purchase_price NUMERIC(12, 2) NOT NULL,
            delivery_price NUMERIC(12, 2) DEFAULT 0 NOT NULL,
            sale_price NUMERIC(12, 2) DEFAULT NULL,
            is_sold TINYINT(1) DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_PLANNING_GOOD_BATCH (planning_good_batch_id),
            INDEX IDX_PLANNING_GOOD_WAREHOUSE (warehouse_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_PLANNING_GOOD_BATCH FOREIGN KEY (planning_good_batch_id)
                REFERENCES planning_good_batches (id) ON DELETE CASCADE,
            CONSTRAINT FK_PLANNING_GOOD_WAREHOUSE FOREIGN KEY (warehouse_id)
                REFERENCES warehouses (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE planning_goods DROP FOREIGN KEY FK_PLANNING_GOOD_BATCH');
        $this->addSql('ALTER TABLE planning_goods DROP FOREIGN KEY FK_PLANNING_GOOD_WAREHOUSE');
        $this->addSql('DROP TABLE planning_goods');
        $this->addSql('DROP TABLE planning_good_batches');
    }
}
