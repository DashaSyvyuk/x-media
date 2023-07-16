<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230716190418 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE supplier_order_product (id INT UNSIGNED AUTO_INCREMENT NOT NULL, supplier_order_id INT UNSIGNED DEFAULT NULL, product_id INT UNSIGNED DEFAULT NULL, quantity INT NOT NULL, price INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_37ED2D211605B9 (supplier_order_id), INDEX IDX_37ED2D214584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE supplier_order_product ADD CONSTRAINT FK_37ED2D211605B9 FOREIGN KEY (supplier_order_id) REFERENCES supplier_orders (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE supplier_order_product ADD CONSTRAINT FK_37ED2D214584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE orders RENAME INDEX fk_e52ffdeeb5e4a15f TO IDX_E52FFDEEB5E4A15F');
        $this->addSql('ALTER TABLE orders RENAME INDEX fk_e52ffdeef216ee8e TO IDX_E52FFDEEF216EE8E');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE supplier_order_product DROP FOREIGN KEY FK_37ED2D211605B9');
        $this->addSql('ALTER TABLE supplier_order_product DROP FOREIGN KEY FK_37ED2D214584665A');
        $this->addSql('DROP TABLE supplier_order_product');
        $this->addSql('ALTER TABLE orders RENAME INDEX idx_e52ffdeef216ee8e TO FK_E52FFDEEF216EE8E');
        $this->addSql('ALTER TABLE orders RENAME INDEX idx_e52ffdeeb5e4a15f TO FK_E52FFDEEB5E4A15F');
    }
}
