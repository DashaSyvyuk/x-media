<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240708182734 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE delivery_type ADD need_address_field TINYINT(1) NOT NULL, ADD is_nova_poshta TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE orders ADD nova_poshta_city_id INT UNSIGNED DEFAULT NULL AFTER address, ADD nova_poshta_office_id INT UNSIGNED DEFAULT NULL after address');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEEEA61FD6 FOREIGN KEY (nova_poshta_city_id) REFERENCES nova_poshta_city (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE2FE96810 FOREIGN KEY (nova_poshta_office_id) REFERENCES nova_poshta_office (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_E52FFDEEEEA61FD6 ON orders (nova_poshta_city_id)');
        $this->addSql('CREATE INDEX IDX_E52FFDEE2FE96810 ON orders (nova_poshta_office_id)');
        $this->addSql('ALTER TABLE delivery_type ADD address VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE delivery_type DROP need_address_field, DROP is_nova_poshta');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEEEA61FD6');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE2FE96810');
        $this->addSql('DROP INDEX IDX_E52FFDEEEEA61FD6 ON orders');
        $this->addSql('DROP INDEX IDX_E52FFDEE2FE96810 ON orders');
        $this->addSql('ALTER TABLE orders DROP nova_poshta_city_id, DROP nova_poshta_office_id');
        $this->addSql('ALTER TABLE delivery_type DROP address');
    }
}
