<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220514132552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nova_poshta_city (id INT UNSIGNED AUTO_INCREMENT NOT NULL, ref VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nova_poshta_office (id INT UNSIGNED AUTO_INCREMENT NOT NULL, city_id INT UNSIGNED DEFAULT NULL, ref VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_CEF5D45A8BAC62AF (city_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE nova_poshta_office ADD CONSTRAINT FK_CEF5D45A8BAC62AF FOREIGN KEY (city_id) REFERENCES nova_poshta_city (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nova_poshta_office DROP FOREIGN KEY FK_CEF5D45A8BAC62AF');
        $this->addSql('DROP TABLE nova_poshta_city');
        $this->addSql('DROP TABLE nova_poshta_office');
    }
}
