<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241005095846 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE return_product (id INT UNSIGNED AUTO_INCREMENT NOT NULL, supplier_id INT UNSIGNED DEFAULT NULL, product_id INT UNSIGNED DEFAULT NULL, status VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, surname VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, ttn VARCHAR(255) DEFAULT NULL, amount INT NOT NULL, reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_C56216722ADD6D8C (supplier_id), INDEX IDX_C56216724584665A (product_id), INDEX IDX_C56216727B00651C (status), INDEX IDX_C56216728B8E8428 (created_at), INDEX IDX_C562167243625D9F (updated_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE return_product ADD CONSTRAINT FK_C56216722ADD6D8C FOREIGN KEY (supplier_id) REFERENCES suppliers (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE return_product ADD CONSTRAINT FK_C56216724584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE return_product DROP FOREIGN KEY FK_C56216722ADD6D8C');
        $this->addSql('ALTER TABLE return_product DROP FOREIGN KEY FK_C56216724584665A');
        $this->addSql('DROP TABLE return_product');
    }
}
