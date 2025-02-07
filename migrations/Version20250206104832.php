<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250206104832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value ADD value_id INT UNSIGNED DEFAULT NULL, ADD string_value LONGTEXT NOT NULL, DROP value');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value ADD CONSTRAINT FK_AD0154AEF920BBA2 FOREIGN KEY (value_id) REFERENCES rozetka_characteristics_values (id)');
        $this->addSql('CREATE INDEX IDX_AD0154AEF920BBA2 ON product_rozetka_characteristic_value (value_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value DROP FOREIGN KEY FK_AD0154AEF920BBA2');
        $this->addSql('DROP INDEX IDX_AD0154AEF920BBA2 ON product_rozetka_characteristic_value');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value ADD value VARCHAR(255) NOT NULL, DROP value_id, DROP string_value');
    }
}
