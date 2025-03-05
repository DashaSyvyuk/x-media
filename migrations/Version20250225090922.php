<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250225090922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rozetka_characteristic_category (rozetka_characteristic_id INT UNSIGNED NOT NULL, category_id INT UNSIGNED NOT NULL, INDEX IDX_C8621C811DF33789 (rozetka_characteristic_id), INDEX IDX_C8621C8112469DE2 (category_id), PRIMARY KEY(rozetka_characteristic_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');$this->addSql('ALTER TABLE rozetka_characteristic_category ADD CONSTRAINT FK_C8621C811DF33789 FOREIGN KEY (rozetka_characteristic_id) REFERENCES characteristics_rozetka (id)');
        $this->addSql('ALTER TABLE rozetka_characteristic_category ADD CONSTRAINT FK_C8621C8112469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('INSERT INTO rozetka_characteristic_category (rozetka_characteristic_id, category_id) SELECT characteristics_rozetka.id, characteristics_rozetka.category_id FROM characteristics_rozetka;');
        $this->addSql('ALTER TABLE characteristics_rozetka DROP FOREIGN KEY FK_CE49F77012469DE2');
        $this->addSql('DROP INDEX IDX_CE49F77012469DE2 ON characteristics_rozetka');
        $this->addSql('ALTER TABLE characteristics_rozetka DROP category_id');
        $this->addSql('ALTER TABLE rozetka_characteristics_values DROP FOREIGN KEY FK_22AC030BDEE9D12B');
        $this->addSql('ALTER TABLE rozetka_characteristics_values CHANGE characteristic_id characteristic_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_CE49F77012391873 ON characteristics_rozetka (rozetka_id)');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('UPDATE rozetka_characteristics_values SET characteristic_id = (SELECT rozetka_id FROM characteristics_rozetka WHERE characteristics_rozetka.id = rozetka_characteristics_values.characteristic_id);');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
        $this->addSql('ALTER TABLE rozetka_characteristic_category DROP FOREIGN KEY FK_C8621C811DF33789');
        $this->addSql('UPDATE rozetka_characteristic_category SET rozetka_characteristic_id = (SELECT rozetka_id FROM characteristics_rozetka WHERE characteristics_rozetka.id = rozetka_characteristic_category.rozetka_characteristic_id);');
        $this->addSql('ALTER TABLE rozetka_characteristics_values CHANGE characteristic_id characteristic_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE characteristics_rozetka CHANGE rozetka_id rozetka_id INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE rozetka_characteristic_category ADD CONSTRAINT FK_C8621C811DF33789 FOREIGN KEY (rozetka_characteristic_id) REFERENCES characteristics_rozetka (rozetka_id)');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value DROP FOREIGN KEY FK_AD0154AEDEE9D12B');
        $this->addSql('UPDATE product_rozetka_characteristic_value SET characteristic_id = (SELECT rozetka_id FROM characteristics_rozetka WHERE characteristics_rozetka.id = product_rozetka_characteristic_value.characteristic_id);');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value ADD CONSTRAINT FK_AD0154AEDEE9D12B FOREIGN KEY (characteristic_id) REFERENCES characteristics_rozetka (rozetka_id)');
        $this->addSql('ALTER TABLE characteristics_rozetka MODIFY id INT UNSIGNED NOT NULL');
        $this->addSql('DROP INDEX `primary` ON characteristics_rozetka');
        $this->addSql('ALTER TABLE characteristics_rozetka DROP id');
        $this->addSql('ALTER TABLE characteristics_rozetka ADD PRIMARY KEY (rozetka_id)');
        $this->addSql('ALTER TABLE rozetka_characteristics_values ADD CONSTRAINT FK_22AC030BDEE9D12B FOREIGN KEY (characteristic_id) REFERENCES characteristics_rozetka (rozetka_id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rozetka_characteristic_category DROP FOREIGN KEY FK_C8621C811DF33789');
        $this->addSql('ALTER TABLE rozetka_characteristic_category DROP FOREIGN KEY FK_C8621C8112469DE2');
        $this->addSql('DROP TABLE rozetka_characteristic_category');
        $this->addSql('ALTER TABLE characteristics_rozetka ADD category_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE characteristics_rozetka ADD CONSTRAINT FK_CE49F77012469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_CE49F77012469DE2 ON characteristics_rozetka (category_id)');
        $this->addSql('DROP INDEX IDX_CE49F77012391873 ON characteristics_rozetka');
        $this->addSql('ALTER TABLE rozetka_characteristics_values DROP FOREIGN KEY FK_22AC030BDEE9D12B');
        $this->addSql('ALTER TABLE rozetka_characteristics_values CHANGE characteristic_id characteristic_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE rozetka_characteristics_values ADD CONSTRAINT FK_22AC030BDEE9D12B FOREIGN KEY (characteristic_id) REFERENCES characteristics_rozetka (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE characteristics_rozetka ADD id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE rozetka_id rozetka_id INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value DROP FOREIGN KEY FK_AD0154AEDEE9D12B');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value ADD CONSTRAINT FK_AD0154AEDEE9D12B FOREIGN KEY (characteristic_id) REFERENCES characteristics_rozetka (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE rozetka_characteristic_category DROP FOREIGN KEY FK_C8621C811DF33789');
        $this->addSql('ALTER TABLE rozetka_characteristic_category ADD CONSTRAINT FK_C8621C811DF33789 FOREIGN KEY (rozetka_characteristic_id) REFERENCES characteristics_rozetka (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE rozetka_characteristics_values CHANGE characteristic_id characteristic_id INT DEFAULT NULL');
    }
}
