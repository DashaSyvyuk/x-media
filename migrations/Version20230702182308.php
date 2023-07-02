<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230702182308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE warehouses (id INT UNSIGNED AUTO_INCREMENT NOT NULL, admin_user_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, notes LONGTEXT DEFAULT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_AFE9C2B76352511C (admin_user_id), INDEX IDX_AFE9C2B72B36786B (title), INDEX IDX_AFE9C2B72D5B0234 (city), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE warehouses ADD CONSTRAINT FK_AFE9C2B76352511C FOREIGN KEY (admin_user_id) REFERENCES admin_user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE warehouses DROP FOREIGN KEY FK_AFE9C2B76352511C');
        $this->addSql('DROP TABLE warehouses');
    }
}
