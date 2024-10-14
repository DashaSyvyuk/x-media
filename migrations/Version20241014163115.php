<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241014163115 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE warranty (id INT UNSIGNED AUTO_INCREMENT NOT NULL, supplier_id INT UNSIGNED DEFAULT NULL, product_id INT UNSIGNED DEFAULT NULL, status VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, surname VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, from_client_ttn VARCHAR(255) DEFAULT NULL, to_client_ttn VARCHAR(255) DEFAULT NULL, supplier_order_number VARCHAR(255) DEFAULT NULL, order_number VARCHAR(255) DEFAULT NULL, expenses INT NOT NULL, reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_88D71CF22ADD6D8C (supplier_id), INDEX IDX_88D71CF24584665A (product_id), INDEX IDX_88D71CF27B00651C (status), INDEX IDX_88D71CF28B8E8428 (created_at), INDEX IDX_88D71CF243625D9F (updated_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE warranty ADD CONSTRAINT FK_88D71CF22ADD6D8C FOREIGN KEY (supplier_id) REFERENCES suppliers (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE warranty ADD CONSTRAINT FK_88D71CF24584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE warranty DROP FOREIGN KEY FK_88D71CF22ADD6D8C');
        $this->addSql('ALTER TABLE warranty DROP FOREIGN KEY FK_88D71CF24584665A');
        $this->addSql('DROP TABLE warranty');
    }
}
