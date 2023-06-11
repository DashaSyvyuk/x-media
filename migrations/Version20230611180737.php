<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230611180737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE delivery_type DROP COLUMN cost');
        $this->addSql('ALTER TABLE delivery_type ADD cost INT NOT NULL');
        $this->addSql('ALTER TABLE payment_type ADD cost INT NOT NULL');
        $this->addSql('ALTER TABLE delivery_type ADD icon VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_type ADD icon VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE delivery_type CHANGE cost cost VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE payment_type DROP cost');
        $this->addSql('ALTER TABLE delivery_type DROP icon');
        $this->addSql('ALTER TABLE payment_type DROP icon');
    }
}
