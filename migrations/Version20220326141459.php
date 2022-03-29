<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220326141459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_filter_attribute DROP FOREIGN KEY FK_A21D47EA474BCE6D');
        $this->addSql('ALTER TABLE product_filter_attribute ADD CONSTRAINT FK_A21D47EA474BCE6D FOREIGN KEY (filter_attribute_id) REFERENCES filter_attributes (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_filter_attribute DROP FOREIGN KEY FK_A21D47EA474BCE6D');
        $this->addSql('ALTER TABLE product_filter_attribute ADD CONSTRAINT FK_A21D47EA474BCE6D FOREIGN KEY (filter_attribute_id) REFERENCES filter_attributes (id)');
    }
}
