<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230802201212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product ADD product_code2 VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD parent_id INT UNSIGNED DEFAULT NULL, ADD show_in_header TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE category ADD hotline_link VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP product_code2');
        $this->addSql('ALTER TABLE category ADD parent_id INT UNSIGNED DEFAULT NULL, ADD show_in_header TINYINT(1) NOT NULL, DROP hotline_link');
        $this->addSql('ALTER TABLE category DROP parent_id, DROP show_in_header');
    }
}
