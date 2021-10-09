<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211008201715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE filters ADD category_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE filters ADD CONSTRAINT FK_7877678D12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_7877678D12469DE2 ON filters (category_id)');
        $this->addSql('ALTER TABLE product_filter_attribute ADD id INT UNSIGNED AUTO_INCREMENT NOT NULL, ADD filter_id INT UNSIGNED NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE product_filter_attribute ADD CONSTRAINT FK_A21D47EAD395B25E FOREIGN KEY (filter_id) REFERENCES filters (id)');
        $this->addSql('CREATE INDEX IDX_A21D47EAD395B25E ON product_filter_attribute (filter_id)');
        $this->addSql('CREATE INDEX IDX_A21D47EA4584665AD395B25E474BCE6D ON product_filter_attribute (product_id, filter_id, filter_attribute_id)');
        $this->addSql('ALTER TABLE product CHANGE note note LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE filters DROP FOREIGN KEY FK_7877678D12469DE2');
        $this->addSql('DROP INDEX IDX_7877678D12469DE2 ON filters');
        $this->addSql('ALTER TABLE filters DROP category_id');
        $this->addSql('ALTER TABLE product_filter_attribute MODIFY id INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE product_filter_attribute DROP FOREIGN KEY FK_A21D47EAD395B25E');
        $this->addSql('DROP INDEX IDX_A21D47EAD395B25E ON product_filter_attribute');
        $this->addSql('DROP INDEX IDX_A21D47EA4584665AD395B25E474BCE6D ON product_filter_attribute');
        $this->addSql('ALTER TABLE product_filter_attribute DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE product_filter_attribute DROP id, DROP filter_id');
        $this->addSql('ALTER TABLE product_filter_attribute ADD PRIMARY KEY (filter_attribute_id, product_id)');
        $this->addSql('ALTER TABLE product CHANGE note note LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
