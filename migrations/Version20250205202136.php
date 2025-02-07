<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250205202136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value_value MODIFY id INT UNSIGNED NOT NULL');
        $this->addSql('DROP INDEX IDX_10D6046B1C771D31370EBEE2 ON product_rozetka_characteristic_value_value');
        $this->addSql('DROP INDEX `primary` ON product_rozetka_characteristic_value_value');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value_value DROP id, CHANGE rozetka_characteristic_value_id rozetka_characteristic_value_id INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value_value ADD PRIMARY KEY (product_rozetka_characteristic_value_id, rozetka_characteristic_value_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value_value ADD id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE rozetka_characteristic_value_id rozetka_characteristic_value_id INT UNSIGNED DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('CREATE INDEX IDX_10D6046B1C771D31370EBEE2 ON product_rozetka_characteristic_value_value (product_rozetka_characteristic_value_id, rozetka_characteristic_value_id)');
    }
}
