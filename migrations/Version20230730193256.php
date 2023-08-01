<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230730193256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category CHANGE position position INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE filter_attributes CHANGE priority priority INT DEFAULT NULL');
        $this->addSql('ALTER TABLE filters CHANGE priority priority INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product_characteristic CHANGE position position INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE product_image CHANGE position position INT UNSIGNED DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category CHANGE position position INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE filter_attributes CHANGE priority priority INT NOT NULL');
        $this->addSql('ALTER TABLE filters CHANGE priority priority INT NOT NULL');
        $this->addSql('ALTER TABLE product_characteristic CHANGE position position INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE product_image CHANGE position position INT UNSIGNED NOT NULL');
    }
}
