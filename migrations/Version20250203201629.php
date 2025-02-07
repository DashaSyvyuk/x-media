<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250203201629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_rozetka_characteristic_value_value (id INT UNSIGNED AUTO_INCREMENT NOT NULL, product_rozetka_characteristic_value_id INT UNSIGNED NOT NULL, rozetka_characteristic_value_id INT UNSIGNED DEFAULT NULL, value VARCHAR(255) NOT NULL, INDEX IDX_10D6046B1C771D31 (product_rozetka_characteristic_value_id), INDEX IDX_10D6046B370EBEE2 (rozetka_characteristic_value_id), INDEX IDX_10D6046B1C771D31370EBEE2 (product_rozetka_characteristic_value_id, rozetka_characteristic_value_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value_value ADD CONSTRAINT FK_10D6046B1C771D31 FOREIGN KEY (product_rozetka_characteristic_value_id) REFERENCES product_rozetka_characteristic_value (id)');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value_value ADD CONSTRAINT FK_10D6046B370EBEE2 FOREIGN KEY (rozetka_characteristic_value_id) REFERENCES rozetka_characteristics_values (id)');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value DROP FOREIGN KEY FK_AD0154AEF920BBA2');
        $this->addSql('DROP INDEX IDX_AD0154AEF920BBA2 ON product_rozetka_characteristic_value');
        $this->addSql('DROP INDEX IDX_AD0154AEA0EAF80ADEE9D12BF920BBA2 ON product_rozetka_characteristic_value');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value DROP value_id');
        $this->addSql('CREATE INDEX IDX_AD0154AEA0EAF80ADEE9D12B ON product_rozetka_characteristic_value (rozetka_product_id, characteristic_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value_value DROP FOREIGN KEY FK_10D6046B1C771D31');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value_value DROP FOREIGN KEY FK_10D6046B370EBEE2');
        $this->addSql('DROP TABLE product_rozetka_characteristic_value_value');
        $this->addSql('DROP INDEX IDX_AD0154AEA0EAF80ADEE9D12B ON product_rozetka_characteristic_value');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value ADD value_id INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value ADD CONSTRAINT FK_AD0154AEF920BBA2 FOREIGN KEY (value_id) REFERENCES rozetka_characteristics_values (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_AD0154AEF920BBA2 ON product_rozetka_characteristic_value (value_id)');
        $this->addSql('CREATE INDEX IDX_AD0154AEA0EAF80ADEE9D12BF920BBA2 ON product_rozetka_characteristic_value (rozetka_product_id, characteristic_id, value_id)');
    }
}
