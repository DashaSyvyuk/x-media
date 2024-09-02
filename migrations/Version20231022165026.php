<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231022165026 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE supplier_orders CHANGE date_time date_time DATETIME DEFAULT NULL, CHANGE expected_date expected_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD availability VARCHAR(255) NOT NULL DEFAULT "в наявності" AFTER status;');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE supplier_orders CHANGE date_time date_time DATETIME NOT NULL, CHANGE expected_date expected_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE product DROP availability');
    }
}
