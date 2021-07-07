<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210707092805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_filter_attribute (filter_attribute_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL, INDEX IDX_A21D47EA474BCE6D (filter_attribute_id), INDEX IDX_A21D47EA4584665A (product_id), PRIMARY KEY(filter_attribute_id, product_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product_filter_attribute ADD CONSTRAINT FK_A21D47EA474BCE6D FOREIGN KEY (filter_attribute_id) REFERENCES filter_attributes (id)');
        $this->addSql('ALTER TABLE product_filter_attribute ADD CONSTRAINT FK_A21D47EA4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE filter_attributes DROP FOREIGN KEY FK_EFF6EB794584665A');
        $this->addSql('DROP INDEX IDX_EFF6EB794584665A ON filter_attributes');
        $this->addSql('ALTER TABLE filter_attributes DROP product_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE product_filter_attribute');
        $this->addSql('ALTER TABLE filter_attributes ADD product_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE filter_attributes ADD CONSTRAINT FK_EFF6EB794584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_EFF6EB794584665A ON filter_attributes (product_id)');
    }
}
