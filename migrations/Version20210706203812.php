<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210706203812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE filter_parameter_value DROP FOREIGN KEY FK_63F720858DFB3B41');
        $this->addSql('CREATE TABLE filter_attributes (id INT UNSIGNED AUTO_INCREMENT NOT NULL, filter_id INT UNSIGNED DEFAULT NULL, product_id INT UNSIGNED DEFAULT NULL, value VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_EFF6EB79D395B25E (filter_id), INDEX IDX_EFF6EB794584665A (product_id), INDEX IDX_EFF6EB791D775834 (value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE filters (id INT UNSIGNED AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_7877678D2B36786B (title), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE filter_attributes ADD CONSTRAINT FK_EFF6EB79D395B25E FOREIGN KEY (filter_id) REFERENCES filters (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE filter_attributes ADD CONSTRAINT FK_EFF6EB794584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL');
        $this->addSql('DROP TABLE filter_parameter');
        $this->addSql('DROP TABLE filter_parameter_value');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE filter_attributes DROP FOREIGN KEY FK_EFF6EB79D395B25E');
        $this->addSql('CREATE TABLE filter_parameter (id INT UNSIGNED AUTO_INCREMENT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_FC7C5DFD2B36786B (title), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE filter_parameter_value (id INT UNSIGNED AUTO_INCREMENT NOT NULL, filter_parameter_id INT UNSIGNED DEFAULT NULL, product_id INT UNSIGNED DEFAULT NULL, value VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_63F720854584665A (product_id), INDEX IDX_63F720858DFB3B41 (filter_parameter_id), INDEX IDX_63F720851D775834 (value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE filter_parameter_value ADD CONSTRAINT FK_63F720854584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE filter_parameter_value ADD CONSTRAINT FK_63F720858DFB3B41 FOREIGN KEY (filter_parameter_id) REFERENCES filter_parameter (id) ON DELETE SET NULL');
        $this->addSql('DROP TABLE filter_attributes');
        $this->addSql('DROP TABLE filters');
    }
}
