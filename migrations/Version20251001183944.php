<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251001183944 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_queues DROP FOREIGN KEY FK_ADB643322ADD6D8C');
        $this->addSql('ALTER TABLE product_queues DROP FOREIGN KEY FK_ADB643324584665A');
        $this->addSql('ALTER TABLE product_queues DROP FOREIGN KEY FK_ADB643325080ECDE');
        $this->addSql('DROP TABLE product_queues');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_queues (id INT UNSIGNED AUTO_INCREMENT NOT NULL, product_id INT UNSIGNED DEFAULT NULL, supplier_id INT UNSIGNED DEFAULT NULL, warehouse_id INT UNSIGNED DEFAULT NULL, price INT NOT NULL, quantity INT NOT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_ADB643322ADD6D8C (supplier_id), INDEX IDX_ADB643324584665A (product_id), INDEX IDX_ADB643325080ECDE (warehouse_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE product_queues ADD CONSTRAINT FK_ADB643322ADD6D8C FOREIGN KEY (supplier_id) REFERENCES suppliers (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE product_queues ADD CONSTRAINT FK_ADB643324584665A FOREIGN KEY (product_id) REFERENCES product (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE product_queues ADD CONSTRAINT FK_ADB643325080ECDE FOREIGN KEY (warehouse_id) REFERENCES warehouses (id) ON UPDATE NO ACTION ON DELETE SET NULL');
    }
}
