<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241028182143 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category ADD show_in_ekatalog_feed TINYINT(1) DEFAULT 1 NOT NULL, ADD show_in_prom_feed TINYINT(1) DEFAULT 1 NOT NULL, ADD show_in_rozetka_feed TINYINT(1) DEFAULT 1 NOT NULL, ADD show_in_hotline_feed TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP show_in_ekatalog_feed, DROP show_in_prom_feed, DROP show_in_rozetka_feed, DROP show_in_hotline_feed');
    }
}
