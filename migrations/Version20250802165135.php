<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250802165135 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rozetka_product ADD rozetka_product_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE rozetka_product ADD CONSTRAINT FK_7DDE2317A0EAF80A FOREIGN KEY (rozetka_product_id) REFERENCES rozetka_product (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7DDE2317A0EAF80A ON rozetka_product (rozetka_product_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rozetka_product DROP FOREIGN KEY FK_7DDE2317A0EAF80A');
        $this->addSql('DROP INDEX UNIQ_7DDE2317A0EAF80A ON rozetka_product');
        $this->addSql('ALTER TABLE rozetka_product DROP rozetka_product_id');
    }
}
