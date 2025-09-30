<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250930133934 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE in_stock (id INT UNSIGNED AUTO_INCREMENT NOT NULL, product_id INT UNSIGNED NOT NULL, warehouse_id INT UNSIGNED NOT NULL, quantity INT UNSIGNED NOT NULL, purchase_price NUMERIC(12, 2) DEFAULT NULL, INDEX IDX_1C3481E9FF31636 (quantity), INDEX IDX_1C3481E1E03A1F3 (purchase_price), INDEX IDX_1C3481E4584665A (product_id), INDEX IDX_1C3481E5080ECDE (warehouse_id), UNIQUE INDEX UNIQ_1C3481E4584665A5080ECDE (product_id, warehouse_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE in_stock ADD CONSTRAINT FK_1C3481E4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE in_stock ADD CONSTRAINT FK_1C3481E5080ECDE FOREIGN KEY (warehouse_id) REFERENCES warehouses (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE in_stock DROP FOREIGN KEY FK_1C3481E4584665A');
        $this->addSql('ALTER TABLE in_stock DROP FOREIGN KEY FK_1C3481E5080ECDE');
        $this->addSql('DROP TABLE in_stock');
    }
}
