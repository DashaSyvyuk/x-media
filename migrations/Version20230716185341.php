<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230716185341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE supplier_orders (id INT UNSIGNED AUTO_INCREMENT NOT NULL, supplier_id INT UNSIGNED DEFAULT NULL, admin_user_id INT DEFAULT NULL, order_number VARCHAR(255) DEFAULT NULL, date_time DATETIME NOT NULL, expected_date DATETIME NOT NULL, status TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_3E4D2F3A2ADD6D8C (supplier_id), INDEX IDX_3E4D2F3A6352511C (admin_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE supplier_orders ADD CONSTRAINT FK_3E4D2F3A2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES suppliers (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE supplier_orders ADD CONSTRAINT FK_3E4D2F3A6352511C FOREIGN KEY (admin_user_id) REFERENCES admin_user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE supplier_orders DROP FOREIGN KEY FK_3E4D2F3A2ADD6D8C');
        $this->addSql('ALTER TABLE supplier_orders DROP FOREIGN KEY FK_3E4D2F3A6352511C');
        $this->addSql('DROP TABLE supplier_orders');
    }
}
