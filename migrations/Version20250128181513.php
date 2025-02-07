<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250128181513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE characteristics_rozetka (id INT UNSIGNED AUTO_INCREMENT NOT NULL, category_id INT UNSIGNED DEFAULT NULL, rozetka_id INT NOT NULL, title VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, filter_type TINYINT(1) NOT NULL, unit VARCHAR(255) NOT NULL, end_to_end_parameter TINYINT(1) NOT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_CE49F77012469DE2 (category_id), INDEX IDX_CE49F7702B36786B (title), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_rozetka_characteristic_value (id INT UNSIGNED AUTO_INCREMENT NOT NULL, rozetka_product_id INT UNSIGNED NOT NULL, characteristic_id INT UNSIGNED NOT NULL, value_id INT UNSIGNED NOT NULL, INDEX IDX_AD0154AEA0EAF80A (rozetka_product_id), INDEX IDX_AD0154AEDEE9D12B (characteristic_id), INDEX IDX_AD0154AEF920BBA2 (value_id), INDEX IDX_AD0154AEA0EAF80ADEE9D12BF920BBA2 (rozetka_product_id, characteristic_id, value_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rozetka_characteristics_values (id INT UNSIGNED AUTO_INCREMENT NOT NULL, characteristic_id INT UNSIGNED DEFAULT NULL, rozetka_id VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_22AC030BDEE9D12B (characteristic_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rozetka_product (id INT UNSIGNED AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, stock_quantity INT NOT NULL, article VARCHAR(255) NOT NULL, series VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, price INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE characteristics_rozetka ADD CONSTRAINT FK_CE49F77012469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value ADD CONSTRAINT FK_AD0154AEA0EAF80A FOREIGN KEY (rozetka_product_id) REFERENCES rozetka_product (id)');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value ADD CONSTRAINT FK_AD0154AEDEE9D12B FOREIGN KEY (characteristic_id) REFERENCES characteristics_rozetka (id)');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value ADD CONSTRAINT FK_AD0154AEF920BBA2 FOREIGN KEY (value_id) REFERENCES rozetka_characteristics_values (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rozetka_characteristics_values ADD CONSTRAINT FK_22AC030BDEE9D12B FOREIGN KEY (characteristic_id) REFERENCES characteristics_rozetka (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE product ADD rozetka_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12391873 FOREIGN KEY (rozetka_id) REFERENCES rozetka_product (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04AD12391873 ON product (rozetka_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12391873');
        $this->addSql('ALTER TABLE characteristics_rozetka DROP FOREIGN KEY FK_CE49F77012469DE2');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value DROP FOREIGN KEY FK_AD0154AEA0EAF80A');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value DROP FOREIGN KEY FK_AD0154AEDEE9D12B');
        $this->addSql('ALTER TABLE product_rozetka_characteristic_value DROP FOREIGN KEY FK_AD0154AEF920BBA2');
        $this->addSql('ALTER TABLE rozetka_characteristics_values DROP FOREIGN KEY FK_22AC030BDEE9D12B');
        $this->addSql('DROP TABLE characteristics_rozetka');
        $this->addSql('DROP TABLE product_rozetka_characteristic_value');
        $this->addSql('DROP TABLE rozetka_characteristics_values');
        $this->addSql('DROP TABLE rozetka_product');
        $this->addSql('DROP INDEX UNIQ_D34A04AD12391873 ON product');
        $this->addSql('ALTER TABLE product DROP rozetka_id');
    }
}
